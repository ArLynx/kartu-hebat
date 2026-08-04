<x-app-layout>
    <div class="mx-auto max-w-3xl">
        <div class="mb-7">
            <p class="section-kicker">Keamanan operator</p>
            <h1 class="mt-2 text-3xl font-extrabold">Aktifkan Autentikasi Dua Faktor</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Superadmin dan seluruh role operator wajib mengaktifkan 2FA sebelum menggunakan modul internal.
            </p>
        </div>

        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-800">
            Pindai QR code menggunakan aplikasi authenticator, konfirmasi kode enam digit, lalu simpan recovery codes di tempat aman.
        </div>

        <div class="card p-6 sm:p-8">
            @livewire('two-factor-setup-page')
        </div>
    </div>
</x-app-layout>
