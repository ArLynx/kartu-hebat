<x-guest-layout>
    <!-- Header Section -->
    <div class="flex flex-col items-center text-center gap-2 mb-6">
        <div
            class="h-16 w-16 bg-surface-container rounded-full flex items-center justify-center mb-2 border border-border-subtle">
            <span class="material-symbols-outlined text-primary"
                style="font-size: 32px; font-variation-settings: 'FILL' 1;">security</span>
        </div>
        <h1 class="font-headline-md text-headline-md text-primary">Verifikasi Dua Langkah</h1>
        <p class="font-body-md text-body-md text-on-surface-variant" x-show="!recovery">
            Masukkan kode autentikasi dari aplikasi authenticator Anda.
        </p>
        <p class="font-body-md text-body-md text-on-surface-variant" x-cloak x-show="recovery">
            Masukkan salah satu kode pemulihan darurat Anda.
        </p>
    </div>

    <!-- Validasi Error -->
    <x-validation-errors class="mb-4" />

    <!-- Form 2FA -->
    <div x-data="{ recovery: false }">
        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <!-- Input Authentication Code -->
            <div x-show="!recovery" class="animate-fade-in">
                <label class="block font-label-md text-label-md text-on-surface mb-2" for="code">Kode
                    Autentikasi</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-outline">
                        <span class="material-symbols-outlined text-[20px]">pin</span>
                    </div>
                    <input id="code" type="text" inputmode="numeric" name="code" x-ref="code" autofocus
                        autocomplete="one-time-code"
                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-outline-variant bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-md text-body-md text-on-surface placeholder-outline-variant tracking-widest text-center text-lg"
                        placeholder="••••••">
                </div>
            </div>

            <!-- Input Recovery Code -->
            <div x-cloak x-show="recovery" class="animate-fade-in">
                <label class="block font-label-md text-label-md text-on-surface mb-2" for="recovery_code">Kode
                    Pemulihan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-outline">
                        <span class="material-symbols-outlined text-[20px]">key</span>
                    </div>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code"
                        autocomplete="one-time-code"
                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-outline-variant bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-md text-body-md text-on-surface placeholder-outline-variant tracking-widest text-center text-lg"
                        placeholder="xxxxx-xxxxx">
                </div>
            </div>

            <!-- Toggle Buttons -->
            <div class="flex items-center justify-center pt-2">
                <button type="button"
                    class="font-label-sm text-label-sm text-primary hover:text-primary-container hover:underline transition-colors focus:outline-none cursor-pointer"
                    x-show="!recovery" x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    Gunakan kode pemulihan
                </button>

                <button type="button"
                    class="font-label-sm text-label-sm text-primary hover:text-primary-container hover:underline transition-colors focus:outline-none cursor-pointer"
                    x-cloak x-show="recovery" x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    Gunakan kode autentikasi
                </button>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit"
                    class="w-full py-3 px-4 flex items-center justify-center space-x-2 bg-primary text-on-primary font-label-md text-label-md rounded-lg hover:bg-primary-container transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <span>Verifikasi</span>
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                </button>
            </div>
        </form>

        <!-- Back to Login Link -->
        <div class="text-center mt-6 pt-4 border-t border-border-subtle">
            <a href="{{ route('login') }}"
                class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Kembali ke halaman login
            </a>
        </div>
    </div>
</x-guest-layout>