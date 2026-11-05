<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Sanggahan;
use App\Models\User;
use App\Models\VerificationLog;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SanggahanService
{
    /**
     * Alasan pengajuan sanggahan ditolak, atau null bila application layak.
     */
    public function ineligibilityReason(Application $application, User $student): ?string
    {
        $application->loadMissing(['mahasiswa.profile.village', 'pendaftaran.periode']);

        if ($student->role !== UserRole::MAHASISWA) {
            return 'Sanggahan hanya dapat diajukan oleh mahasiswa pemilik pengajuan.';
        }

        if ((int) $application->mahasiswa_id !== (int) $student->getKey()) {
            return 'Sanggahan hanya dapat diajukan untuk pengajuan milik Anda sendiri.';
        }

        if ($application->status !== ApplicationStatus::TMS) {
            return 'Sanggahan hanya dapat diajukan untuk pengajuan berstatus Tidak Memenuhi Syarat (TMS).';
        }

        $periode = $application->resolvedPeriode();

        if (! $periode || $periode->status !== 'aktif') {
            return 'Sanggahan hanya dapat diajukan selama periode beasiswa masih aktif.';
        }

        if (! $periode->isMasaSanggahOpen()) {
            return 'Masa sanggah periode ini belum dibuka atau sudah berakhir.';
        }

        if ($application->sanggahan()->exists()) {
            return 'Sanggahan untuk pengajuan ini sudah pernah diajukan dan tidak dapat diubah.';
        }

        return null;
    }

    public function submit(Application $application, User $student, string $uraian): Sanggahan
    {
        return DB::transaction(function () use ($application, $student, $uraian): Sanggahan {
            $application = Application::query()
                ->lockForUpdate()
                ->findOrFail($application->getKey());

            $reason = $this->ineligibilityReason($application, $student);

            if ($reason !== null) {
                throw ValidationException::withMessages(['sanggahan' => $reason]);
            }

            $periode = $application->resolvedPeriode();

            $sanggahan = Sanggahan::query()->create([
                'application_id' => $application->id,
                'periode_id' => $periode->id,
                'submitted_by' => $student->id,
                'status' => SanggahanStatus::DIAJUKAN,
                'uraian' => trim($uraian),
            ]);

            VerificationLog::query()->create([
                'application_id' => $application->id,
                'actor_id' => $student->id,
                'from_status' => ApplicationStatus::TMS->value,
                'to_status' => ApplicationStatus::TMS->value,
                'action' => 'sanggahan_submitted',
                'notes' => 'Sanggahan diajukan oleh mahasiswa.',
                'metadata' => ['sanggahan_id' => $sanggahan->id],
                'created_at' => now(),
            ]);

            $this->notifyKabupatenOperators($application);

            return $sanggahan;
        });
    }

    private function notifyKabupatenOperators(Application $application): void
    {
        $application->loadMissing('mahasiswa.profile.village');

        $kabupatenId = $application->mahasiswa?->profile?->village?->kabupaten_id;

        if (! $kabupatenId) {
            return;
        }

        User::query()
            ->where('role', UserRole::OPERATOR_KABUPATEN->value)
            ->where('status', 'active')
            ->where('kabupaten_id', $kabupatenId)
            ->get()
            ->each(function (User $operator) use ($application): void {
                $operator->notify(new ApplicationStatusChanged(
                    $application,
                    'Sanggahan baru diajukan',
                    'Sanggahan untuk pengajuan '.$application->nomor_pengajuan.' menunggu pemeriksaan.',
                    route('operator.applications.show', $application, absolute: false),
                ));
            });
    }
}
