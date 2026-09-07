<x-guest-layout>
    <x-slot name="title">Verifikasi Dua Langkah</x-slot>

    <div x-data="{ recovery: false }">
        <div class="mb-8">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200">
                <x-icon name="shield" class="h-6 w-6" />
            </div>
            <p class="section-kicker">Keamanan Akun</p>
            <h1 class="mt-3 text-3xl font-extrabold">Verifikasi Dua Langkah</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600" x-show="!recovery">
                Masukkan kode autentikasi dari aplikasi authenticator Anda.
            </p>
            <p class="mt-2 text-sm leading-6 text-slate-600" x-cloak x-show="recovery">
                Masukkan salah satu kode pemulihan darurat Anda.
            </p>
        </div>

        <x-validation-errors class="mb-5" />

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <!-- Input Authentication Code -->
            <div x-show="!recovery">
                <label for="code" class="form-label">Kode Autentikasi</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <x-icon name="shield" class="h-5 w-5" />
                    </div>
                    <input id="code"
                        type="text"
                        inputmode="numeric"
                        name="code"
                        x-ref="code"
                        autofocus
                        autocomplete="one-time-code"
                        class="form-input !pl-11 text-center font-mono text-lg font-semibold tracking-widest placeholder:font-sans placeholder:tracking-normal"
                        placeholder="••••••">
                </div>
                <p class="form-help">Buka aplikasi authenticator (Google Authenticator, dll) untuk melihat kode aktif.</p>
            </div>

            <!-- Input Recovery Code -->
            <div x-cloak x-show="recovery">
                <label for="recovery_code" class="form-label">Kode Pemulihan</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <x-icon name="key" class="h-5 w-5" />
                    </div>
                    <input id="recovery_code"
                        type="text"
                        name="recovery_code"
                        x-ref="recovery_code"
                        autocomplete="one-time-code"
                        class="form-input !pl-11 text-center font-mono text-base font-semibold tracking-wider placeholder:font-sans placeholder:tracking-normal"
                        placeholder="xxxxx-xxxxx">
                </div>
                <p class="form-help">Gunakan salah satu kode pemulihan darurat yang Anda simpan saat aktivasi 2FA.</p>
            </div>

            <!-- Toggle Mode -->
            <div class="flex items-center justify-end">
                <button type="button"
                    class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline focus:outline-none cursor-pointer"
                    x-show="!recovery"
                    x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    Gunakan kode pemulihan
                </button>

                <button type="button"
                    class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline focus:outline-none cursor-pointer"
                    x-cloak
                    x-show="recovery"
                    x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    Gunakan kode autentikasi
                </button>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-primary w-full justify-center">
                <span>Verifikasi</span>
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>

        <!-- Back to Login Link -->
        <div class="mt-8 border-t border-slate-200 pt-5 text-center">
            <a href="{{ route('login') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-brand-600 hover:underline transition">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali ke halaman login
            </a>
        </div>
    </div>
</x-guest-layout>