<?php

namespace App\Services;

use App\Enums\ApplicationType;
use App\Models\JenisDokumen;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MahasiswaPendaftaranService
{
    /**
     * Mengambil pendaftaran terakhir milik akun mahasiswa yang sedang login.
     */
    public function currentFor(User $user): ?Pendaftaran
    {
        return Pendaftaran::query()
            ->where('user_id', $user->getKey())
            ->whereHas('periode', fn ($query) => $query->aktif())
            ->latest('id')
            ->with(['application.documents.type', 'application.documents.verifications'])
            ->first();
    }

    public function isEditable(Pendaftaran $pendaftaran): bool
    {
        return in_array($pendaftaran->status, ['draft', 'revision'], true);
    }

    /**
     * Semua jenis dokumen pada pivot kategori dianggap wajib.
     * Jika master persyaratan kosong, tahap dokumen sengaja dianggap belum lengkap.
     *
     * @return Collection<int, JenisDokumen>
     */
    public function requiredDocumentTypes(Pendaftaran $pendaftaran): Collection
    {
        return JenisDokumen::query()
            ->select('jenis_dokumens.*')
            ->join(
                'kategori_beasiswa_dokumens',
                'kategori_beasiswa_dokumens.jenis_dokumen_id',
                '=',
                'jenis_dokumens.id',
            )
            ->where('kategori_beasiswa_dokumens.kategori_beasiswa_id', $pendaftaran->kategori_beasiswa_id)
            ->where('jenis_dokumens.aktif', true)
            ->orderBy('kategori_beasiswa_dokumens.urutan')
            ->orderBy('jenis_dokumens.id')
            ->get();
    }

    /**
     * @return array<int, bool>
     */
    public function completion(Pendaftaran $pendaftaran): array
    {
        $pendaftaran->loadMissing([
            'kategoriBeasiswa',
            'dataPribadi',
            'pendidikan',
            'prestasis',
            'orangTua',
            'dokumens',
            'application.documents.type',
            'application.documents.verifications',
        ]);

        $data = $pendaftaran->dataPribadi;
        $pendidikan = $pendaftaran->pendidikan;
        $orangTua = $pendaftaran->orangTua;

        $dataPribadiComplete = $data
            && $this->allFilled($data, [
                'nik',
                'nama_lengkap',
                'tempat_lahir',
                'tanggal_lahir',
                'jenis_kelamin',
                'alamat',
                'village_id',
                'provinsi',
                'kabupaten',
                'kecamatan',
                'desa',
                'no_hp',
            ]);

        $pendidikanComplete = $pendidikan
            && $this->allFilled($pendidikan, [
                'nim',
                'universitas',
                'fakultas',
                'program_studi',
                'jenjang',
                'semester',
                'ipk',
                'tahun_masuk',
                'status_mahasiswa',
            ]);

        $prestasiComplete = ! $this->requiresPrestasi($pendaftaran)
            || $pendaftaran->prestasi_dikonfirmasi_at !== null;

        $ayahFields = ['nama_ayah', 'status_ayah', 'pekerjaan_ayah', 'penghasilan_ayah'];
        if ($orangTua?->status_ayah === 'hidup') {
            $ayahFields[] = 'nik_ayah';
        }

        $ibuFields = ['nama_ibu', 'status_ibu', 'pekerjaan_ibu', 'penghasilan_ibu'];
        if ($orangTua?->status_ibu === 'hidup') {
            $ibuFields[] = 'nik_ibu';
        }

        $waliFields = ['nama_wali', 'nik_wali', 'pekerjaan_wali', 'penghasilan_wali'];

        $orangTuaComplete = $orangTua
            && $this->allFilled($orangTua, array_merge($ayahFields, $ibuFields))
            && (! $orangTua->memiliki_wali || $this->allFilled($orangTua, $waliFields));

        $requiredTypes = $this->requiredDocumentTypes($pendaftaran);
        $uploadedDocuments = $pendaftaran->dokumens
            ->keyBy(fn ($document): int => (int) $document->jenis_dokumen_id);
        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        $documentsComplete = $requiredTypes->isNotEmpty()
            && $requiredTypes->every(function (JenisDokumen $type) use ($uploadedDocuments, $disk): bool {
                $document = $uploadedDocuments->get((int) $type->getKey());

                return $document
                    && filled($document->file_path)
                    && $disk->exists($document->file_path);
            });

        $prerequisitesComplete = $dataPribadiComplete
            && $pendidikanComplete
            && $prestasiComplete
            && $orangTuaComplete
            && $documentsComplete;

        $reviewConfirmed = $pendaftaran->review_dikonfirmasi_at !== null
            && ! (
                $pendaftaran->status === 'revision'
                && $pendaftaran->submitted_at !== null
                && $pendaftaran->review_dikonfirmasi_at->lessThanOrEqualTo($pendaftaran->submitted_at)
            );

        $reviewComplete = $prerequisitesComplete && $reviewConfirmed;

        $submitted = ! in_array($pendaftaran->status, ['draft', 'revision'], true);

        return [
            1 => (bool) $dataPribadiComplete,
            2 => (bool) $pendidikanComplete,
            3 => (bool) $prestasiComplete,
            4 => (bool) $orangTuaComplete,
            5 => (bool) $documentsComplete,
            6 => (bool) $reviewComplete,
            7 => (bool) $submitted,
        ];
    }

    public function requiresPrestasi(Pendaftaran $pendaftaran): bool
    {
        $pendaftaran->loadMissing('kategoriBeasiswa');

        return $pendaftaran->kategoriBeasiswa?->application_type === ApplicationType::NON_AKADEMIK;
    }

    /**
     * @return array<int, string>
     */
    public function missingStageLabels(Pendaftaran $pendaftaran, bool $includeReview = false): array
    {
        $labels = [
            1 => 'Data Pribadi',
            2 => 'Pendidikan',
            3 => 'Prestasi',
            4 => 'Orang Tua',
            5 => 'Dokumen',
        ];

        if (! $this->requiresPrestasi($pendaftaran)) {
            unset($labels[3]);
        }

        if ($includeReview) {
            $labels[6] = 'Review';
        }

        $completion = $this->completion($pendaftaran);

        return collect($labels)
            ->reject(fn (string $label, int $step): bool => $completion[$step])
            ->values()
            ->all();
    }

    private function allFilled(object $model, array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            $value = $model->{$attribute};

            if ($value === null || (is_string($value) && trim($value) === '')) {
                return false;
            }
        }

        return true;
    }
}
