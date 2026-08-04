@php
    $steps = [
        1 => ['label' => 'Data Pribadi', 'route' => 'mahasiswa.data-pribadi.index'],
        2 => ['label' => 'Pendidikan', 'route' => 'mahasiswa.pendidikan.index'],
        3 => ['label' => 'Prestasi', 'route' => 'mahasiswa.prestasi.index'],
        4 => ['label' => 'Orang Tua', 'route' => 'mahasiswa.orang-tua.index'],
        5 => ['label' => 'Dokumen', 'route' => 'mahasiswa.dokumen.index'],
        6 => ['label' => 'Review', 'route' => 'mahasiswa.review.index'],
        7 => ['label' => 'Submit', 'route' => 'mahasiswa.submit.index'],
    ];
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-3xl font-extrabold">{{ $title }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $description }}</p>
    </div>
    <div class="text-left sm:text-right">
        <span class="inline-flex w-fit rounded-full bg-brand-100 px-4 py-2 text-xs font-bold text-brand-800">
            Tahap {{ $currentStep }} / 7
        </span>
        <p class="mt-2 text-xs text-slate-500">{{ $pendaftaran->nomor_pendaftaran }}</p>
    </div>
</div>

<div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
    @foreach($steps as $number => $step)
        @php
            $completed = (bool) ($stepStatuses[$number] ?? false);
            $current = $number === $currentStep;
        @endphp
        <a href="{{ route($step['route']) }}"
           @class([
               'rounded-xl border p-4 text-center transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
               'border-brand-300 bg-brand-50 shadow-sm' => $current,
               'border-emerald-200 bg-emerald-50 hover:border-emerald-300' => $completed && ! $current,
               'border-slate-200 bg-slate-50 hover:border-brand-200 hover:bg-white' => ! $completed && ! $current,
           ])>
            <div @class([
                'mx-auto flex h-9 w-9 items-center justify-center rounded-full font-bold',
                'bg-brand-700 text-white' => $current,
                'bg-emerald-600 text-white' => $completed && ! $current,
                'bg-white text-slate-500' => ! $completed && ! $current,
            ])>
                @if($completed && ! $current)
                    <x-icon name="check" class="h-4 w-4" />
                @else
                    {{ $number }}
                @endif
            </div>
            <p class="mt-3 text-xs font-semibold text-slate-700">{{ $step['label'] }}</p>
        </a>
    @endforeach
</div>
