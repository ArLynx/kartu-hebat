<x-guest-layout>
    <div class="mb-8">
        <p class="section-kicker">Verifikasi Akun</p>
        <h1 class="mt-3 text-3xl font-extrabold text-navy-950">Verifikasi Email Anda</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Untuk mengamankan akun dan melanjutkan pengisian beasiswa, silakan verifikasi alamat email Anda.
        </p>
    </div>

    @if (session('verification_mail_error'))
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <x-icon name="warning" class="h-5 w-5" />
                </div>
                <div>
                    <p class="font-bold text-amber-900">Email Verifikasi Tertunda</p>
                    <p class="mt-1 leading-5 text-amber-800">
                        {{ session('verification_mail_error') }}
                    </p>
                </div>
            </div>
        </div>
    @elseif (session('status') == 'verification-link-sent')
        <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <x-icon name="check" class="h-5 w-5" />
                </div>
                <div>
                    <p class="font-bold text-emerald-900">Tautan Verifikasi Baru Terkirim</p>
                    <p class="mt-1 leading-5 text-emerald-800">
                        Kami telah mengirimkan tautan verifikasi baru ke email Anda. Silakan periksa folder <strong>Kotak Masuk (Inbox)</strong> atau folder <strong>Spam</strong>.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-brand-600">
                <x-icon name="envelope" class="h-6 w-6" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Email Terdaftar</p>
                <p class="mt-1 truncate text-base font-bold text-slate-900">
                    {{ auth()->user()?->email }}
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Tautan verifikasi telah kami kirimkan ke alamat di atas. Silakan buka email Anda dan klik tautan verifikasi untuk mengaktifkan akun.
                </p>
            </div>
        </div>

        <div class="mt-6 border-t border-slate-100 pt-5">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center">
                    <x-icon name="arrow-path" class="h-4 w-4" />
                    Kirim Ulang Email Verifikasi
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        @if (Route::has('profile.show'))
            <a href="{{ route('profile.show') }}" class="font-semibold text-slate-600 hover:text-brand-600 hover:underline">
                Perbaiki Alamat Email
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="font-semibold text-red-600 hover:text-red-700 hover:underline">
                Keluar / Ganti Akun
            </button>
        </form>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-500">
        <p class="font-semibold text-slate-700">Tidak menerima email?</p>
        <ul class="mt-1 list-inside list-disc space-y-1">
            <li>Pastikan koneksi internet di perangkat Anda aktif.</li>
            <li>Periksa folder Spam atau Promosi pada email Anda.</li>
            <li>Pastikan penulisan alamat email Anda di atas sudah benar.</li>
        </ul>
    </div>
</x-guest-layout>
