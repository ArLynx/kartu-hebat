@props([
    'application',
    'prestasiDocumentMimes' => [],
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @forelse($application->pendaftaran?->prestasis ?? [] as $p)
        @php
            $prestasiMime = $prestasiDocumentMimes[$p->id] ?? null;
            $isPrestasiPreviewable = in_array($prestasiMime, ['application/pdf', 'image/jpeg', 'image/png'], true);
        @endphp
        <div class="rounded border border-slate-200 p-2 text-sm">
            <div class="font-semibold text-slate-900">{{ $p->nama_prestasi }} ({{ $p->tahun ?? '-' }})</div>
            <div class="text-xs text-slate-500">
                @if($p->kategoriEnum() === \App\Enums\KategoriPrestasi::ORGANISASI)
                    Organisasi · {{ $p->posisiEnum()?->label() ?? 'Pengurus' }} · {{ $p->tingkatOrganisasiEnum()?->label() ?? 'Universitas' }}
                @else
                    Lomba · Tingkat {{ \App\Models\Prestasi::TINGKAT_OPTIONS[$p->tingkat] ?? $p->tingkat }} · Peringkat {{ $p->peringkat }}
                @endif
            </div>
            <div class="text-xs text-slate-500">Penyelenggara: {{ $p->penyelenggara ?? '-' }}</div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                @if(filled($p->dokumen_prestasi) && $prestasiMime !== null)
                    @if($isPrestasiPreviewable)
                        <button
                            type="button"
                            class="btn-secondary !px-3 !py-1.5 text-xs"
                            @click="openPreview(
                                {{ Js::from(route('operator.applications.prestasi.lihat', [$application, $p])) }},
                                {{ Js::from(route('operator.applications.prestasi.unduh', [$application, $p])) }},
                                {{ Js::from($p->kategoriEnum() === \App\Enums\KategoriPrestasi::ORGANISASI ? 'SK Organisasi' : 'Sertifikat Prestasi') }},
                                {{ Js::from($prestasiMime) }}
                            )"
                        >
                            <x-icon name="eye" class="h-3.5 w-3.5" />
                            Lihat
                        </button>
                    @endif
                    <a href="{{ route('operator.applications.prestasi.unduh', [$application, $p]) }}" class="btn-secondary !px-3 !py-1.5 text-xs">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Unduh
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                        <x-icon name="x" class="h-3 w-3" /> Berkas bukti belum diunggah
                    </span>
                @endif
            </div>
        </div>
    @empty
        <span class="text-sm text-slate-500">Belum ada data prestasi.</span>
    @endforelse
</div>
