<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'bail',
                'required',
                'string',
                Password::min(8)->mixedCase()->symbols(),
                'confirmed',
            ],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : [],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email aktif wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi wajib minimal 8 karakter.',
            'password.mixed' => 'Kata sandi wajib mengandung minimal 1 huruf kapital (A-Z) dan huruf kecil (a-z).',
            'password.symbols' => 'Kata sandi wajib mengandung minimal 1 karakter tanda/simbol (contoh: @, #, $, !, _).',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ])->validate();

        $user = new User([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
            'password' => Hash::make($input['password']),
        ]);

        $user->forceFill(['role' => UserRole::MAHASISWA, 'status' => 'active'])->save();

        return $user;
    }
}
