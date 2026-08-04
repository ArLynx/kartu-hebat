@props(['compact' => false, 'light' => false])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ $light ? 'border-white/20 bg-white' : 'border-slate-300 bg-white' }} p-1">
        <img src="{{ asset('images/logo-murung-raya.png') }}" alt="Lambang Kabupaten Murung Raya" class="h-full w-full object-contain">
    </div>
    @unless($compact)
        <div class="leading-tight">
            <p class="font-display text-base font-extrabold {{ $light ? 'text-white' : 'text-navy-900' }}">Kartu Hebat</p>
            <p class="text-xs {{ $light ? 'text-slate-300' : 'text-slate-500' }}">Kab. Murung Raya</p>
        </div>
    @endunless
</div>
