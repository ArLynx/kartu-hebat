<x-guest-layout>
    <x-slot name="title">Atur Ulang Kata Sandi</x-slot>

    <div class="mb-8">
        <p class="section-kicker">Pembaruan Keamanan</p>
        <h1 class="mt-3 text-3xl font-extrabold">Atur ulang kata sandi</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Masukkan kata sandi baru untuk akun Anda.</p>
    </div>

    <x-validation-errors class="mb-5" />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-input" autocomplete="username" autofocus required>
        </div>

        <div>
            <label for="password" class="form-label">Kata Sandi Baru</label>
            <input id="password" type="password" name="password" class="form-input" autocomplete="new-password" required>
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            Simpan Kata Sandi Baru
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>
</x-guest-layout>
