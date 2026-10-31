<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected StatefulGuard $guard
    ) {}

    /**
     * Daftarkan mahasiswa baru dengan pengiriman email verifikasi terintegrasi.
     * Jika pengiriman email verifikasi gagal (misal koneksi internet putus / SMTP offline),
     * pembuatan akun otomatis di-rollback dari database dan mahasiswa dikembalikan
     * ke formulir pendaftaran dengan data nama & email yang tetap terisi.
     */
    public function store(Request $request, CreatesNewUsers $creator): RegisterResponse|RedirectResponse
    {
        if (config('fortify.lowercase_usernames') && $request->has(Fortify::username())) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        try {
            $user = DB::transaction(function () use ($creator, $request) {
                // 1. Validasi & buat akun di database
                $user = $creator->create($request->all());

                // 2. Kirim email verifikasi saat itu juga di dalam transaksi
                event(new Registered($user));

                return $user;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning("Pendaftaran mahasiswa dibatalkan karena pengiriman email verifikasi ke {$request->input('email')} gagal: {$e->getMessage()}");

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'Pendaftaran akun belum berhasil karena email verifikasi tidak dapat dikirimkan. Pastikan perangkat Anda terhubung ke internet dan alamat email aktif/benar, lalu coba klik Daftar kembali.',
                ]);
        }

        if ($request->hasSession()) {
            $request->session()->forget([
                'password_hash_web',
                'password_hash_sanctum',
                'url.intended',
            ]);
            $request->session()->regenerate();
        }

        $this->guard->login($user, $request->boolean('remember'));

        return app(RegisterResponse::class);
    }
}
