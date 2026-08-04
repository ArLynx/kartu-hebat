<?php

namespace App\Actions\Fortify;

use App\Enums\ApplicationStatus;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update account identity fields managed by Fortify.
     * Domain data mahasiswa is updated only from the registration module.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $validator->after(function ($validator) use ($user, $input): void {
            if (!$user->isStudent() || ($input['name'] ?? $user->name) === $user->name) {
                return;
            }

            $hasLockedCurrentApplication = $user->applications()
                ->where('periode', config('kartu_hebat.current_period'))
                ->whereNotIn('status', [
                    ApplicationStatus::DRAFT->value,
                    ApplicationStatus::BTL_DESA->value,
                    ApplicationStatus::BTL_KECAMATAN->value,
                ])
                ->exists();

            if ($hasLockedCurrentApplication) {
                $validator->errors()->add(
                    'name',
                    'Nama tidak dapat diubah ketika pengajuan periode berjalan sedang diproses.',
                );
            }
        });

        $validator->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);

            return;
        }

        $user->forceFill([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
        ])->save();
    }

    /**
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
