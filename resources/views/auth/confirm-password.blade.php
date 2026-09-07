<x-guest-layout>
    <x-slot name="title">Konfirmasi Kata Sandi</x-slot>

    <div class="mb-8">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200">
            <x-icon name="shield" class="h-6 w-6" />
        </div>
        <p class="section-kicker">Area Aman</p>
        <h1 class="mt-3 text-3xl font-extrabold">Konfirmasi kata sandi</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Ini adalah area aman aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
        </p>
    </div>

    <x-validation-errors class="mb-5" />

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="form-label">Kata Sandi</label>
            <input id="password" type="password" name="password" class="form-input" autocomplete="current-password" autofocus required>
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            Konfirmasi
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>
</x-guest-layout>
