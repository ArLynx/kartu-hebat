<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8">
        <div>
            <p class="section-kicker">Pengaturan akun</p>
            <h1 class="mt-2 text-3xl font-extrabold">Profil dan Keamanan</h1>
            <p class="mt-2 text-sm text-slate-600">Kelola identitas akun, kata sandi, 2FA, dan sesi perangkat.</p>
        </div>

        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            <div class="card p-6 sm:p-8">
                @livewire('profile.update-profile-information-form')
            </div>
        @endif

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            <div class="card p-6 sm:p-8">
                @livewire('profile.update-password-form')
            </div>
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <div class="card p-6 sm:p-8">
                @livewire('profile.two-factor-authentication-form')
            </div>
        @endif

        <div class="card p-6 sm:p-8">
            @livewire('profile.logout-other-browser-sessions-form')
        </div>

        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures() && auth()->user()->isStudent())
            <div class="card border-red-200 p-6 sm:p-8">
                @livewire('profile.delete-user-form')
            </div>
        @endif
    </div>
</x-app-layout>
