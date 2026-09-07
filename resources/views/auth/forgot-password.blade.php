<x-guest-layout>
    <x-slot name="title">Lupa Kata Sandi</x-slot>

    <div class="mb-8">
        <p class="section-kicker">Pemulihan Akun</p>
        <h1 class="mt-3 text-3xl font-extrabold">Lupa kata sandi?</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Masukkan alamat email terdaftar Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
        </p>
    </div>

    <x-validation-errors class="mb-5" />

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" autocomplete="username" autofocus required placeholder="nama@email.com">
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            Kirim Tautan Pengaturan Ulang
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-600">
        Ingat kata sandi Anda?
        <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:underline">Kembali ke login</a>
    </p>
</x-guest-layout>
