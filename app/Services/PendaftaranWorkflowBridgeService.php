<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PendaftaranWorkflowBridgeService
{
    public function __construct(
        private readonly ApplicationWorkflowService $workflow,
    ) {
    }

    public function submit(Pendaftaran $pendaftaran, User $student): Application
    {
        if ((int) $pendaftaran->user_id !== (int) $student->getKey()) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Pendaftaran tidak dimiliki oleh akun mahasiswa yang sedang masuk.',
            ]);
        }

        $pendaftaran->loadMissing([
            'periode',
            'kategoriBeasiswa',
            'dataPribadi',
            'pendidikan',
            'prestasis',
            'orangTua',
            'dokumens.jenisDokumen',
        ]);

        $village = $this->resolveVillage($pendaftaran);
        $applicationType = $this->resolveApplicationType($pendaftaran);

        $this->synchronizeStudentProfile($pendaftaran, $student, $village);
        $application = $this->synchronizeApplication($pendaftaran, $student, $applicationType);
        $this->synchronizeDocuments($pendaftaran, $application, $student, $applicationType);

        return $this->workflow->submit($application->fresh(), $student->fresh());
    }

    private function resolveVillage(Pendaftaran $pendaftaran): Village
    {
        $data = $pendaftaran->dataPribadi;

        if (! $data) {
            throw ValidationException::withMessages([
                'data_pribadi' => 'Data pribadi belum tersedia.',
            ]);
        }

        if ($data->village_id) {
            return Village::query()
                ->with('kecamatan.kabupaten')
                ->findOrFail($data->village_id);
        }

        $districtName = $this->normalizeRegionName($data->kecamatan);
        $villageName = $this->normalizeRegionName($data->desa);
        $countyName = $this->normalizeRegionName($data->kabupaten);

        $matches = Village::query()
            ->with('kecamatan.kabupaten')
            ->get()
            ->filter(function (Village $village) use ($districtName, $villageName, $countyName): bool {
                return $this->normalizeRegionName($village->name) === $villageName
                    && $this->normalizeRegionName($village->kecamatan?->name) === $districtName
                    && $this->normalizeRegionName($village->kabupaten?->name) === $countyName;
            })
            ->values();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'village_id' => $matches->isEmpty()
                    ? 'Desa/kelurahan tidak ditemukan pada master wilayah. Pilih wilayah kembali pada tahap Data Pribadi.'
                    : 'Wilayah yang dimasukkan bersifat ambigu. Pilih desa/kelurahan dari master wilayah.',
            ]);
        }

        $village = $matches->first();

        $data->forceFill([
            'village_id' => $village->id,
            'kabupaten' => $village->kabupaten->name,
            'kecamatan' => $village->kecamatan->name,
            'desa' => $village->name,
        ])->save();

        return $village;
    }

    private function resolveApplicationType(Pendaftaran $pendaftaran): ApplicationType
    {
        $configured = $pendaftaran->kategoriBeasiswa?->application_type;

        if ($configured instanceof ApplicationType) {
            return $configured;
        }

        if (is_string($configured) && ($type = ApplicationType::tryFrom($configured))) {
            return $type;
        }

        $identity = Str::lower(trim(
            ($pendaftaran->kategoriBeasiswa?->kode ?? '').' '.($pendaftaran->kategoriBeasiswa?->nama ?? '')
        ));

        if (Str::contains($identity, ['tidak mampu', 'kurang mampu', 'afirmasi', 'sosial ekonomi'])) {
            return ApplicationType::TIDAK_MAMPU;
        }

        if (Str::contains($identity, ['akademik', 'prestasi'])) {
            return ApplicationType::AKADEMIK;
        }

        $default = (string) config('kartu_hebat.integration.default_application_type', ApplicationType::AKADEMIK->value);

        return ApplicationType::tryFrom($default) ?? ApplicationType::AKADEMIK;
    }

    private function synchronizeStudentProfile(
        Pendaftaran $pendaftaran,
        User $student,
        Village $village,
    ): void {
        $data = $pendaftaran->dataPribadi;
        $education = $pendaftaran->pendidikan;
        $parent = $pendaftaran->orangTua;

        if (! $data || ! $education || ! $parent) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Data pribadi, pendidikan, dan orang tua harus lengkap sebelum masuk verifikasi.',
            ]);
        }

        $income = (int) round(
            (float) ($parent->penghasilan_ayah ?? 0)
            + (float) ($parent->penghasilan_ibu ?? 0)
            + ($parent->memiliki_wali ? (float) ($parent->penghasilan_wali ?? 0) : 0)
        );

        $achievements = $pendaftaran->prestasis
            ->map(fn ($achievement): string => implode(' — ', array_filter([
                $achievement->nama_prestasi,
                $achievement->tingkat,
                $achievement->peringkat,
                $achievement->tahun,
            ])))
            ->implode("\n");

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'nik' => $data->nik,
                'nim' => $education->nim,
                'phone' => $data->no_hp,
                'universitas' => $education->universitas,
                'program_studi' => $education->program_studi,
                'semester' => $education->semester,
                'ipk' => $education->ipk,
                'alamat' => $data->alamat,
                'village_id' => $village->id,
                'penghasilan_keluarga' => $income,
                'prestasi' => $achievements !== '' ? $achievements : null,
            ],
        );

        $student->forceFill([
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ])->save();
    }

    private function synchronizeApplication(
        Pendaftaran $pendaftaran,
        User $student,
        ApplicationType $applicationType,
    ): Application {
        $period = $pendaftaran->periode?->nama ?: (string) $pendaftaran->periode?->tahun;

        $application = Application::query()
            ->where('pendaftaran_id', $pendaftaran->id)
            ->orWhere(function ($query) use ($student, $period): void {
                $query
                    ->where('mahasiswa_id', $student->id)
                    ->where('periode', $period);
            })
            ->lockForUpdate()
            ->first();

        if (! $application) {
            $application = new Application([
                'mahasiswa_id' => $student->id,
                'status' => ApplicationStatus::DRAFT,
                'current_step' => 1,
            ]);
        }

        $application->forceFill([
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'mahasiswa_id' => $student->id,
            'periode' => $period,
            'application_type' => $applicationType,
        ])->save();

        return $application;
    }

    private function synchronizeDocuments(
        Pendaftaran $pendaftaran,
        Application $application,
        User $student,
        ApplicationType $applicationType,
    ): void {
        $diskName = (string) config('kartu_hebat.document_disk', 'local');
        $disk = Storage::disk($diskName);

        foreach ($pendaftaran->dokumens as $index => $source) {
            $sourceType = $source->jenisDokumen;

            if (! $sourceType || ! filled($source->file_path) || ! $disk->exists($source->file_path)) {
                continue;
            }

            $documentType = DocumentType::query()->firstOrNew(['code' => $sourceType->kode]);
            $documentType->fill([
                'name' => $sourceType->nama,
                'description' => $sourceType->deskripsi,
                'allowed_mimes' => collect(explode(',', (string) $sourceType->format_file))
                    ->map(fn (string $mime): string => trim(Str::lower($mime)))
                    ->filter()
                    ->values()
                    ->all(),
                'max_size_kb' => $sourceType->maksimal_ukuran,
                'is_required' => true,
                'is_active' => (bool) $sourceType->aktif,
                'sort_order' => $index + 1,
            ]);

            if (! $documentType->exists || $documentType->application_type === null) {
                $documentType->application_type = $this->documentApplicationType($sourceType->kode, $applicationType);
            }

            $documentType->save();

            $existing = $application->documents()
                ->where('document_type_id', $documentType->id)
                ->first();
            $contents = $disk->get($source->file_path);

            $application->documents()->updateOrCreate(
                ['document_type_id' => $documentType->id],
                [
                    'uploaded_by' => $student->id,
                    'path' => $source->file_path,
                    'original_name' => $source->nama_file_asli ?: basename($source->file_path),
                    'mime_type' => $source->mime_type ?: 'application/octet-stream',
                    'size' => $source->ukuran_file ?: strlen($contents),
                    'checksum' => hash('sha256', $contents),
                    'version' => $existing && $existing->path === $source->file_path
                        ? $existing->version
                        : ($existing?->version ?? 0) + 1,
                    'verified_at' => $source->verified_at,
                ],
            );
        }
    }

    private function documentApplicationType(string $code, ApplicationType $applicationType): ?string
    {
        return match (Str::upper($code)) {
            'KTP', 'KK', 'KTM', 'SURAT-AKTIF' => null,
            'KHS' => ApplicationType::AKADEMIK->value,
            'SKTM' => ApplicationType::TIDAK_MAMPU->value,
            default => $applicationType->value,
        };
    }

    private function normalizeRegionName(?string $name): string
    {
        $normalized = Str::of((string) $name)
            ->lower()
            ->squish()
            ->replaceMatches('/^(kabupaten|kab\.?|kota|kecamatan|kec\.?|kelurahan|desa)\s+/u', '')
            ->toString();

        return trim($normalized);
    }
}
