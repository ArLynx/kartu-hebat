<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\DocumentVerification;
use App\Models\Sanggahan;
use App\Models\User;
use App\Models\VerificationLog;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Collection;
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

    /**
     * Putuskan sanggahan. Keputusan bersifat final: baris yang sudah diputuskan
     * tidak dapat diproses ulang.
     */
    public function decide(
        Sanggahan $sanggahan,
        User $operator,
        SanggahanStatus $decision,
        string $notes,
    ): Sanggahan {
        if (! in_array($decision, [SanggahanStatus::DIKABULKAN, SanggahanStatus::DITOLAK], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Keputusan sanggahan harus Kabulkan atau Tolak.',
            ]);
        }

        return DB::transaction(function () use ($sanggahan, $operator, $decision, $notes): Sanggahan {
            $sanggahan = Sanggahan::query()
                ->lockForUpdate()
                ->findOrFail($sanggahan->getKey());

            if ($sanggahan->status->isDecided()) {
                throw ValidationException::withMessages([
                    'decision' => 'Keputusan sanggahan bersifat final dan tidak dapat diubah.',
                ]);
            }

            $application = Application::query()
                ->lockForUpdate()
                ->findOrFail($sanggahan->application_id);

            if ($application->status !== ApplicationStatus::TMS) {
                throw ValidationException::withMessages([
                    'decision' => 'Sanggahan hanya dapat diputuskan selama pengajuan berstatus TMS.',
                ]);
            }

            $notes = trim($notes);

            $sanggahan->update([
                'status' => $decision,
                'catatan_keputusan' => $notes,
                'decided_by' => $operator->id,
                'decided_at' => now(),
            ]);

            return $decision === SanggahanStatus::DIKABULKAN
                ? $this->grant($sanggahan, $application, $operator, $notes)
                : $this->reject($sanggahan, $application, $operator, $notes);
        });
    }

    private function grant(
        Sanggahan $sanggahan,
        Application $application,
        User $operator,
        string $notes,
    ): Sanggahan {
        $annulled = $this->annulledVerifications($application);

        if ($annulled->isEmpty()) {
            throw ValidationException::withMessages([
                'decision' => 'Keputusan TMS yang akan dianulir tidak ditemukan pada pengajuan ini.',
            ]);
        }

        $round = AgencyVerificationService::currentRound($application);
        $agencies = $annulled->pluck('agency')->all();

        $this->revertProfileMutations($application, $agencies);

        DocumentVerification::query()
            ->where('application_id', $application->id)
            ->whereIn('stage', $agencies)
            ->where('round', $round)
            ->get()
            ->each(fn (DocumentVerification $verification) => $verification->delete());

        $annulled->each(fn (AgencyVerification $verification) => $verification->delete());

        $from = $application->status;

        $application->update([
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'catatan' => null,
            'locked_at' => now(),
        ]);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $operator->id,
            'from_status' => $from->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'sanggahan_granted',
            'notes' => $notes,
            'metadata' => [
                'sanggahan_id' => $sanggahan->id,
                'annulled_agencies' => $agencies,
                'annulled_verifications' => $annulled->map(fn (AgencyVerification $verification): array => [
                    'agency' => $verification->agency,
                    'agency_label' => config('kartu_hebat.agencies.'.$verification->agency, $verification->agency),
                    'decision' => $verification->decision->value,
                    'notes' => $verification->notes,
                    'verifier_id' => $verification->verifier_id,
                ])->all(),
            ],
        ]);

        $this->notifyStudent(
            $application,
            'Sanggahan dikabulkan',
            'Sanggahan Anda dikabulkan. Pengajuan '.$application->nomor_pengajuan.' dikembalikan ke dinas terkait untuk verifikasi ulang.',
        );

        $this->notifyReopenedAgencies($application, $agencies);

        return $sanggahan->fresh();
    }

    private function reject(
        Sanggahan $sanggahan,
        Application $application,
        User $operator,
        string $notes,
    ): Sanggahan {
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $operator->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::TMS->value,
            'action' => 'sanggahan_rejected',
            'notes' => $notes,
            'metadata' => ['sanggahan_id' => $sanggahan->id],
        ]);

        $this->notifyStudent(
            $application,
            'Sanggahan ditolak',
            'Sanggahan Anda ditolak. Keputusan TMS pada pengajuan '.$application->nomor_pengajuan.' tetap berlaku dan bersifat final.',
        );

        return $sanggahan->fresh();
    }

    /**
     * @return Collection<int, AgencyVerification>
     */
    private function annulledVerifications(Application $application): Collection
    {
        $requiredAgencies = AgencyVerificationService::requiredAgencies($application);

        return $application->agencyVerifications()
            ->whereIn('agency', $requiredAgencies)
            ->where('decision', VerificationDecision::TMS->value)
            ->get();
    }

    /**
     * @param  array<int, string>  $agencies
     */
    private function revertProfileMutations(Application $application, array $agencies): void
    {
        $profile = $application->mahasiswa?->profile;

        if (! $profile) {
            return;
        }

        if (in_array(UserRole::OPERATOR_DUKCAPIL->agencyCode(), $agencies, true)) {
            $profile->update(['status_kependudukan' => 'belum_diverifikasi']);
        }

        if (in_array(UserRole::OPERATOR_SOSIAL->agencyCode(), $agencies, true)) {
            $profile->update(['desil_sosial' => null]);
        }
    }

    /**
     * @param  array<int, string>  $agencies
     */
    private function notifyReopenedAgencies(Application $application, array $agencies): void
    {
        $application->loadMissing('mahasiswa.profile.village');

        $kabupatenId = $application->mahasiswa?->profile?->village?->kabupaten_id;

        if (! $kabupatenId) {
            return;
        }

        $roles = collect(UserRole::cases())
            ->filter(fn (UserRole $role) => $role->isAgency() && in_array($role->agencyCode(), $agencies, true))
            ->pluck('value')
            ->all();

        User::query()
            ->whereIn('role', $roles)
            ->where('status', 'active')
            ->where('kabupaten_id', $kabupatenId)
            ->get()
            ->each(function (User $agencyOperator) use ($application): void {
                $agencyOperator->notify(new ApplicationStatusChanged(
                    $application,
                    'Verifikasi ulang diperlukan',
                    'Pengajuan '.$application->nomor_pengajuan.' dikembalikan ke dinas Anda karena sanggahan dikabulkan.',
                    route('operator.applications.show', $application, absolute: false),
                ));
            });
    }

    private function notifyStudent(Application $application, string $title, string $message): void
    {
        $application->mahasiswa->notify(new ApplicationStatusChanged(
            $application,
            $title,
            $message,
            route('mahasiswa.sanggahan.index', absolute: false),
        ));
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
