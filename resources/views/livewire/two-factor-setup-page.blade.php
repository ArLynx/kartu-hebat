<div>
    @livewire('profile.two-factor-authentication-form')

    @if($enabled)
        <div class="mt-8 border-t border-slate-200 pt-6">
            <a href="{{ route('dashboard') }}" class="btn-primary w-full justify-center">
                Lanjutkan ke Dashboard
            </a>
        </div>
    @endif

    <div wire:poll.1000ms="checkStatus"></div>
</div>
