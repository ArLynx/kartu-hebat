<?php

use App\Enums\UserRole;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Services\PendaftaranWorkflowBridgeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'kartu-hebat:create-superadmin {email? : Alamat email Superadmin} {--name=Superadmin : Nama akun}',
    function (): int {
        $email = (string) ($this->argument('email') ?: $this->ask('Email Superadmin'));
        $name = trim((string) $this->option('name')) ?: 'Superadmin';
        $password = (string) $this->secret('Password minimal 12 karakter');
        $confirmation = (string) $this->secret('Ulangi password');

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return Command::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'password' => Hash::make($password),
        ])->forceFill([
            'email_verified_at' => now(),
            'role' => UserRole::SUPERADMIN,
            'status' => 'active',
        ])->save();

        $this->info('Akun Superadmin berhasil dibuat/diperbarui: '.$user->email);
        $this->warn('Superadmin wajib mengaktifkan 2FA saat login pertama.');

        return Command::SUCCESS;
    },
)->purpose('Membuat atau mempromosikan akun menjadi Superadmin.');

Artisan::command(
    'kartu-hebat:integrate-pendaftarans {--id=* : Batasi pada ID pendaftaran tertentu}',
    function (): int {
        $bridge = app(PendaftaranWorkflowBridgeService::class);
        $query = Pendaftaran::query()
            ->whereIn('status', ['submitted', 'verification'])
            ->whereDoesntHave('application')
            ->with('user')
            ->orderBy('id');

        $ids = collect($this->option('id'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($ids->isNotEmpty()) {
            $query->whereKey($ids);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Tidak ada pendaftaran lama yang perlu diintegrasikan.');

            return Command::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        $query->eachById(function (Pendaftaran $pendaftaran) use ($bridge, &$success, &$failed): void {
            try {
                DB::transaction(function () use ($bridge, $pendaftaran): void {
                    $locked = Pendaftaran::query()->lockForUpdate()->findOrFail($pendaftaran->id);
                    $bridge->submit($locked, $pendaftaran->user);
                });

                $success++;
                $this->line('Terintegrasi: '.$pendaftaran->nomor_pendaftaran);
            } catch (Throwable $exception) {
                $failed++;
                $this->error($pendaftaran->nomor_pendaftaran.': '.$exception->getMessage());
            }
        });

        $this->newLine();
        $this->info("Selesai. Berhasil: {$success}; gagal: {$failed}.");

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    },
)->purpose('Menghubungkan pendaftaran lama yang sudah submitted ke workflow verifikasi berjenjang.');
