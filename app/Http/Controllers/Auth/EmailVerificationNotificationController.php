<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Fortify;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Kirim ulang email verifikasi dengan penanganan aman saat koneksi offline.
     */
    public function store(Request $request): EmailVerificationNotificationSentResponse|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home'));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning("Gagal mengirim ulang email verifikasi ke {$request->user()->email}: {$e->getMessage()}");

            return back()->with(
                'verification_mail_error',
                'Gagal mengirim ulang email verifikasi karena kendala koneksi internet atau server email. Pastikan koneksi internet Anda aktif, lalu coba beberapa saat lagi.'
            );
        }

        return back()->with('status', Fortify::VERIFICATION_LINK_SENT);
    }
}
