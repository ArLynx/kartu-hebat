<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BeasiswaDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $kabupaten = Kabupaten::query()->first();
        $kecamatan = Kecamatan::query()->first();
        $village = Village::query()->first();

        User::query()->updateOrCreate(
            ['email' => 'mahasiswa@kartuhebat.test'],
            $this->attributes('Mahasiswa Beasiswa', UserRole::MAHASISWA, $village, $kecamatan, $kabupaten),
        );

        User::query()->updateOrCreate(
            ['email' => 'superadmin@kartuhebat.test'],
            $this->attributes('Superadmin Kartu Hebat', UserRole::SUPERADMIN, null, null, $kabupaten),
        );

        $operators = [
            ['desa@kartuhebat.test', 'Operator Desa', UserRole::OPERATOR_DESA],
            ['kecamatan@kartuhebat.test', 'Operator Kecamatan', UserRole::OPERATOR_KECAMATAN],
            ['dukcapil@kartuhebat.test', 'Operator Dukcapil', UserRole::OPERATOR_DUKCAPIL],
            ['sosial@kartuhebat.test', 'Operator Dinas Sosial', UserRole::OPERATOR_SOSIAL],
            ['pendidikan@kartuhebat.test', 'Operator Dinas Pendidikan', UserRole::OPERATOR_PENDIDIKAN],
            ['kabupaten@kartuhebat.test', 'Operator Kabupaten', UserRole::OPERATOR_KABUPATEN],
        ];

        foreach ($operators as [$email, $name, $role]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                $this->attributes($name, $role, $village, $kecamatan, $kabupaten),
            );
        }
    }

    private function attributes(
        string $name,
        UserRole $role,
        ?Village $village,
        ?Kecamatan $kecamatan,
        ?Kabupaten $kabupaten,
    ): array {
        return [
            'name' => $name,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 'active',
            'village_id' => $role === UserRole::OPERATOR_DESA || $role === UserRole::MAHASISWA
                ? $village?->id
                : null,
            'kecamatan_id' => $role === UserRole::OPERATOR_KECAMATAN || $role === UserRole::MAHASISWA
                ? $kecamatan?->id
                : null,
            'kabupaten_id' => $kabupaten?->id,
        ];
    }
}
