<x-guest-layout>
    <div class="mb-8">
        <p class="section-kicker">Pendaftaran mahasiswa</p>
        <h1 class="mt-3 text-3xl font-extrabold">Buat akun baru</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Akun baru otomatis memperoleh role Mahasiswa. Akun internal dibuat oleh pengelola sistem.</p>
    </div>

    <x-validation-errors class="mb-5" />

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="form-label">Nama Lengkap</label>
            <input id="name" name="name" value="{{ old('name') }}" class="form-input" autocomplete="name" autofocus required placeholder="Sesuai identitas resmi">
        </div>

        <div>
            <label for="email" class="form-label">Email Aktif</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" autocomplete="username" required placeholder="nama@email.com">
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="form-label">Kata Sandi</label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" name="password" class="form-input !pr-16" autocomplete="new-password" required>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500" x-text="show ? 'Sembunyi' : 'Lihat'"></button>
            </div>
            <p class="form-help">Gunakan kombinasi yang kuat dan tidak digunakan pada layanan lain.</p>
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required>
        </div>

        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
            <label class="flex items-start gap-2 text-sm text-slate-600">
                <input type="checkbox" name="terms" required class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>Saya menyetujui syarat layanan dan kebijakan privasi.</span>
            </label>
        @endif

        <button class="btn-primary w-full justify-center">
            Daftar Akun
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-600">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:underline">Masuk</a>
    </p>
</x-guest-layout>
