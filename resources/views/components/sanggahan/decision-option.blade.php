@props([
    'value',
    'label',
    'description',
    'tone' => 'brand',
    'checked' => false,
])

@php
    $toneClasses = match ($tone) {
        'success' => 'text-emerald-600 focus:ring-emerald-500',
        'danger' => 'text-rose-600 focus:ring-rose-500',
        default => 'text-brand-600 focus:ring-brand-500',
    };
@endphp

<label {{ $attributes->class(['flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50']) }}>
    <input
        type="radio"
        name="decision"
        value="{{ $value }}"
        class="mt-1 {{ $toneClasses }}"
        required
        @checked($checked)
    >
    <span>
        <span class="block text-sm font-semibold text-slate-900">{{ $label }}</span>
        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $description }}</span>
    </span>
</label>
