<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class VerifyEmailController extends Controller
{
    /**
     * Verifikasi alamat email mahasiswa melalui tautan bertanda tangan (signed URL).
     *
     * - Jika dibuka pada browser/perangkat yang sama (sesi login masih aktif):
     *   langsung masuk ke Beranda Beasiswa (/dashboard).
     * - Jika dibuka pada perangkat/browser berbeda atau sesi sudah keluar:
     *   tetap memverifikasi email, lalu mengarahkan ke halaman Login dengan pesan sukses.
     */
    public function __invoke(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        if ($request->user() && (int) $request->user()->getKey() === (int) $user->getKey()) {
            return app(VerifyEmailResponse::class);
        }

        return redirect()->route('login')->with(
            'status',
            'Email Anda telah berhasil diverifikasi! Silakan masuk ke akun Anda.'
        );
    }
}
