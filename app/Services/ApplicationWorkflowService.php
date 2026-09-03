<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowService
{
    public function __construct(
        private readonly SelectionScoringService $scoring,
        private readonly DocumentVerificationService $documentVerification,
        private readonly AgencyVerificationService $agencyVerification,
    ) {}

    public function submit(Pendaftaran|Application $target, User $student): Application
    {
        if ($target instanceof Pendaftaran) {
            return $this->submitPendaftaran($target, $student);
        }

        return $this->submitApplication($target, $student);
    }

    private function submitPendaftaran(Pendaftaran $pendaftaran, User $student): Application
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

        // Pengiriman ulang setelah aplikasi dikembalikan ke draf membuka putaran
        // baru: penilaian dokumen lama dibersihkan agar dinas menilai ulang.
        $docVerificationService = $this->documentVerification ?? app(DocumentVerificationService::class);
        if ($application->verificationLogs()->where('action', 'submitted')->exists()) {
            $docVerificationService->resetForApplication($application);
        }

        $this->synchronizeDocuments($pendaftaran, $application, $student, $applicationType);

        return $this->submitApplication($application->fresh(), $student->fresh());
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

        $escapedLike = static fn (string $value): string => addcslashes($value, '\\%_');

        $matches = Village::query()
            ->whereHas(
                'kecamatan',
                fn ($query) => $query->where('name', 'like', '%'.$escapedLike($districtName).'%')
            )
            ->whereHas(
                'kecamatan.kabupaten',
                fn ($query) => $query->where('name', 'like', '%'.$escapedLike($countyName).'%')
            )
            ->where('name', 'like', '%'.$escapedLike($villageName).'%')
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

        throw ValidationException::withMessages([
            'application_type' => 'Jalur pengajuan pada kategori beasiswa belum dikonfigurasi dengan benar.',
        ]);
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

        return DB::transaction(function () use ($pendaftaran, $student, $period, $applicationType): Application {
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
        });
    }

    private function synchronizeDocuments(
        Pendaftaran $pendaftaran,
        Application $application,
        User $student,
        ApplicationType $applicationType,
    ): void {
        $diskName = (string) config('kartu_hebat.document_disk', 'local');
        $disk = Storage::disk($diskName);
        $syncedDocumentTypeIds = [];
        $docVerificationService = $this->documentVerification ?? app(DocumentVerificationService::class);

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
            $syncedDocumentTypeIds[] = $documentType->id;

            $existing = $application->documents()
                ->where('document_type_id', $documentType->id)
                ->first();
            $version = $existing && $existing->path === $source->file_path
                ? $existing->version
                : ($existing?->version ?? 0) + 1;

            if ($existing && $existing->path !== $source->file_path) {
                $docVerificationService->resetForDocument($existing);
            }

            $unchanged = $existing && $existing->path === $source->file_path;

            $application->documents()->updateOrCreate(
                ['document_type_id' => $documentType->id],
                [
                    'uploaded_by' => $student->id,
                    'path' => $source->file_path,
                    'original_name' => $source->nama_file_asli ?: basename($source->file_path),
                    'mime_type' => $source->mime_type ?: 'application/octet-stream',
                    'size' => $source->ukuran_file ?: $disk->size($source->file_path),
                    'checksum' => $unchanged
                        ? $existing->checksum
                        : $disk->checksum($source->file_path, ['checksum_algo' => 'sha256']),
                    'version' => $version,
                    'verified_at' => $source->verified_at,
                ],
            );
        }

        $application->documents()
            ->when($syncedDocumentTypeIds !== [], fn ($query) => $query->whereNotIn('document_type_id', $syncedDocumentTypeIds))
            ->when($syncedDocumentTypeIds === [], fn ($query) => $query)
            ->delete();
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

    public function submitApplication(Application $application, User $student): Application
    {
        if (
            $application->mahasiswa_id !== $student->id
            || ! $this->isActiveApplicationPeriod($application)
            || ! $application->status->isEditableByStudent()
        ) {
            throw ValidationException::withMessages([
                'application' => 'Pengajuan ini tidak dapat dikirim atau diubah.',
            ]);
        }

        if (! $application->application_type) {
            throw ValidationException::withMessages([
                'application_type' => 'Pilih jalur pengajuan Akademik atau Tidak Mampu sebelum mengirim pengajuan.',
            ]);
        }

        if (! $student->isProfileComplete()) {
            throw ValidationException::withMessages([
                'profile' => 'Lengkapi data diri, pendidikan, dan alamat sebelum mengirim pengajuan.',
            ]);
        }

        if ($application->application_type === ApplicationType::AKADEMIK && $student->profile?->ipk === null) {
            throw ValidationException::withMessages([
                'ipk' => 'IPK wajib diisi untuk jalur Akademik.',
            ]);
        }

        if ($application->application_type === ApplicationType::NON_AKADEMIK && $student->profile?->ipk === null) {
            throw ValidationException::withMessages([
                'ipk' => 'IPK wajib diisi untuk jalur Non-Akademik sebagai baseline.',
            ]);
        }

        if ($application->application_type === ApplicationType::NON_AKADEMIK && ($application->pendaftaran_id === null || $application->pendaftaran?->prestasis()->exists() === false)) {
            throw ValidationException::withMessages([
                'prestasis' => 'Minimal satu data prestasi wajib diisi untuk jalur Non-Akademik.',
            ]);
        }

        $application->loadMissing('pendaftaran.kategoriBeasiswa.jenisDokumens');
        $requiredSourceTypes = collect($application->pendaftaran?->kategoriBeasiswa?->jenisDokumens ?? [])
            ->where('aktif', true)
            ->values();
        $requiredTypes = DocumentType::query()
            ->whereIn('code', $requiredSourceTypes->pluck('kode'))
            ->where('is_active', true)
            ->get();

        $unmappedCodes = $requiredSourceTypes->pluck('kode')->diff($requiredTypes->pluck('code'));

        if ($unmappedCodes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Jenis dokumen wajib '.$unmappedCodes->implode(', ')
                    .' tidak ditemukan pada data master dokumen. Data master perlu disinkronkan oleh pengelola sebelum pengajuan dapat dikirim.',
            ]);
        }

        $uploadedIds = $application->documents()->pluck('document_type_id');
        $missing = $requiredTypes
            ->except($uploadedIds->all())
            ->pluck('name')
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Dokumen wajib belum lengkap: '.implode(', ', $missing).'.',
            ]);
        }

        return DB::transaction(function () use ($application, $student): Application {
            $from = $application->status;

            $application->update([
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'submitted_at' => $application->submitted_at ?? now(),
                'locked_at' => now(),
                'catatan' => null,
            ]);

            $this->log($application, $from, ApplicationStatus::VERIFIKASI_DINAS, 'submitted', 'Pengajuan dikirim oleh mahasiswa.', $student);
            $this->notifyStudent($application, 'Pengajuan berhasil dikirim', 'Berkas Anda masuk ke tahap '.ApplicationStatus::VERIFIKASI_DINAS->label().'.');
            $this->notifyNextOperators($application, ApplicationStatus::VERIFIKASI_DINAS);

            return $application->fresh();
        });
    }

    public function verify(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes = null,
        ?float $score = null,
        ?int $desil = null,
    ): Application {
        return $this->agencyVerification->submitDecision(
            $application,
            $operator,
            $decision,
            $notes,
            $score,
            $desil,
        );
    }

    private function notifyStudent(Application $application, string $title, string $message): void
    {
        $application->mahasiswa->notify(new ApplicationStatusChanged(
            $application,
            $title,
            $message,
            $application->pendaftaran_id
                ? route('mahasiswa.dashboard', absolute: false)
                : route('student.history', absolute: false),
        ));
    }

    private function notifyNextOperators(Application $application, ApplicationStatus $status): void
    {
        $application->loadMissing('mahasiswa.profile.village');

        $query = User::query()->where('status', 'active');
        $title = 'Pengajuan siap diverifikasi';
        $message = $application->nomor_pengajuan.' siap diproses.';

        match ($status) {
            ApplicationStatus::VERIFIKASI_DINAS => $query
                ->whereIn('role', collect(UserRole::cases())
                    ->filter(fn (UserRole $role) => $role->isAgency())
                    ->filter(fn (UserRole $role) => in_array(
                        $role->agencyCode(),
                        DocumentVerificationService::requiredAgencies($application),
                        true,
                    ))
                    ->pluck('value')
                    ->all())
                ->where('kabupaten_id', $application->mahasiswa->profile->village->kabupaten_id),
            ApplicationStatus::SELEKSI_KABUPATEN => $query
                ->where('role', UserRole::OPERATOR_KABUPATEN->value)
                ->where('kabupaten_id', $application->mahasiswa->profile->village->kabupaten_id),
            default => $query->whereRaw('1 = 0'),
        };

        $query->get()->each(function (User $operator) use ($application, $title, $message): void {
            $operator->notify(new ApplicationStatusChanged(
                $application,
                $title,
                $message,
                route('operator.applications.show', $application, absolute: false),
            ));
        });
    }

    private function isActiveApplicationPeriod(Application $application): bool
    {
        if ($application->pendaftaran_id) {
            $application->loadMissing('pendaftaran.periode');

            $period = $application->pendaftaran?->periode;
            $today = today();

            return $period?->status === 'aktif'
                && $period->tanggal_mulai?->lte($today)
                && $period->tanggal_selesai?->gte($today);
        }

        return $application->periode === config('kartu_hebat.current_period');
    }

    private function log(
        Application $application,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        string $action,
        ?string $notes = null,
        ?User $actor = null,
        array $metadata = [],
    ): void {
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $actor?->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
