<x-guest-layout>
    <div class="mb-8">
        <p class="section-kicker">Akses portal</p>
        <h1 class="mt-3 text-3xl font-extrabold">Masuk ke akun Anda</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Gunakan email terverifikasi dan kata sandi akun Kartu Hebat Mahasiswa.</p>
    </div>

    <x-validation-errors class="mb-5" />

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" autocomplete="username" autofocus required placeholder="nama@email.com">
        </div>

        <div x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">Kata Sandi</label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="mb-2 text-xs font-semibold text-brand-600 hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" name="password" class="form-input !pr-16" autocomplete="current-password" required>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-brand-600" x-text="show ? 'Sembunyi' : 'Lihat'"></button>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Ingat saya pada perangkat ini
        </label>

        <button class="btn-primary w-full justify-center">
            Masuk ke Sistem
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-600">
        Belum memiliki akun?
        <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:underline">Daftar sebagai mahasiswa</a>
    </p>
</x-guest-layout>
