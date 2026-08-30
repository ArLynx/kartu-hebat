<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use App\Services\DocumentVerificationService;

class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        $application->loadMissing('mahasiswa.profile.village');

        if ($user->role === UserRole::MAHASISWA) {
            return $application->mahasiswa_id === $user->id;
        }

        if (! $user->isOperator()) {
            return false;
        }

        $village = $application->mahasiswa->profile?->village;

        if (! $village) {
            return false;
        }

        return match ($user->role) {
            UserRole::OPERATOR_DESA => $user->village_id === $village->id,
            UserRole::OPERATOR_KECAMATAN => $user->kecamatan_id === $village->kecamatan_id,
            default => $user->kabupaten_id === $village->kabupaten_id,
        };
    }

    public function update(User $user, Application $application): bool
    {
        return $user->role === UserRole::MAHASISWA
            && $application->mahasiswa_id === $user->id
            && $application->periode === config('kartu_hebat.current_period')
            && $application->status->isEditableByStudent();
    }

    public function submit(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function verify(User $user, Application $application): bool
    {
        if (
            ! $this->view($user, $application)
            || $application->periode !== config('kartu_hebat.current_period')
        ) {
            return false;
        }

        return match ($user->role) {
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN,
            UserRole::OPERATOR_DINKES,
            UserRole::OPERATOR_PARSEPOR => $application->status === ApplicationStatus::VERIFIKASI_DINAS,
            default => false,
        } && in_array(
            $user->role->agencyCode(),
            DocumentVerificationService::requiredAgencies($application),
            true,
        );
    }

    public function select(User $user, Application $application): bool
    {
        return $this->view($user, $application)
            && $user->role === UserRole::OPERATOR_KABUPATEN
            && $application->periode === config('kartu_hebat.current_period')
            && in_array($application->status, [
                ApplicationStatus::SELEKSI_KABUPATEN,
                ApplicationStatus::DITERIMA,
                ApplicationStatus::DITOLAK,
            ], true);
    }
}
