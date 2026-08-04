@extends('layouts.portal')

@section('title', 'Pendaftaran Saya')
@section('header', 'Pendaftaran Saya')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Formulir periode {{ $application->periode }}</p>
        <h1 class="mt-2 text-3xl font-extrabold">Data dan Dokumen Pendaftaran</h1>
        <p class="mt-2 text-sm text-slate-600">Pilih satu jalur pengajuan, lalu lengkapi data dan dokumen yang sesuai. Pengajuan dikunci setelah dikirim.</p>
    </div>
    <div class="text-left sm:text-right">
        <x-status-badge :status="$application->status" />
        <p class="mt-2 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
    </div>
</div>

@if(!$application->canBeEditedByStudent())
    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        Pengajuan telah dikirim. Data hanya dapat diubah apabila petugas memberikan status Butuh Perbaikan (BTL).
    </div>
@endif

<section class="card mt-7 p-6 sm:p-8">
    <div class="flex items-start gap-4">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
            <x-icon name="user" class="h-6 w-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">Data Mahasiswa</h2>
            <p class="mt-1 text-sm text-slate-500">Jalur pengajuan, data kependudukan, akademik, dan sosial ekonomi.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('student.profile.update') }}" class="mt-7">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$application->canBeEditedByStudent())>
            <div>
                <label class="form-label">Jalur Pengajuan</label>
                <p class="mt-1 text-xs leading-5 text-slate-500">Pilih satu jalur. Ranking dan kuota diproses terpisah untuk setiap jalur.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach($applicationTypes as $type)
                        <label class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 p-5 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-100">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="application_type" value="{{ $type->value }}" class="mt-1"
                                    @checked(old('application_type', $application->application_type?->value) === $type->value) required>
                                <div>
                                    <p class="font-bold text-navy-900">{{ $type->label() }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $type->description() }}</p>
                                    <p class="mt-2 text-xs font-semibold text-brand-700">
                                        {{ $type === \App\Enums\ApplicationType::AKADEMIK ? 'Bobot: IPK 75% + semester 25%' : 'Bobot: desil terverifikasi 100%' }}
                                    </p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-7 grid gap-6 md:grid-cols-2">
            <div>
                <label class="form-label">Nama Lengkap</label>
                <input name="name" class="form-input" value="{{ old('name', auth()->user()->name) }}" required>
            </div>
            <div>
                <label class="form-label">NIK</label>
                <input name="nik" class="form-input" value="{{ old('nik', $profile?->nik) }}" maxlength="16" inputmode="numeric" required>
            </div>
            <div>
                <label class="form-label">NIM</label>
                <input name="nim" class="form-input" value="{{ old('nim', $profile?->nim) }}" required>
            </div>
            <div>
                <label class="form-label">Nomor Telepon/WhatsApp</label>
                <input name="phone" class="form-input" value="{{ old('phone', $profile?->phone) }}">
            </div>
            <div>
                <label class="form-label">Perguruan Tinggi</label>
                <input name="universitas" class="form-input" value="{{ old('universitas', $profile?->universitas) }}" required>
            </div>
            <div>
                <label class="form-label">Program Studi</label>
                <input name="program_studi" class="form-input" value="{{ old('program_studi', $profile?->program_studi) }}" required>
            </div>
            <div>
                <label class="form-label">Semester Aktif</label>
                <input type="number" name="semester" min="1" max="14" class="form-input" value="{{ old('semester', $profile?->semester) }}" required>
            </div>
            <div>
                <label class="form-label">IPK</label>
                <input type="number" step="0.01" name="ipk" min="0" max="4" class="form-input" value="{{ old('ipk', $profile?->ipk) }}">
                <p class="mt-1 text-xs text-slate-500">Wajib untuk jalur Akademik.</p>
            </div>
            <div>
                <label class="form-label">Desa/Kelurahan</label>
                <select name="village_id" class="form-input" required>
                    <option value="">Pilih wilayah</option>
                    @foreach($villages->groupBy(fn($village) => $village->kecamatan->name) as $district => $items)
                        <optgroup label="Kecamatan {{ $district }}">
                            @foreach($items as $village)
                                <option value="{{ $village->id }}" @selected(old('village_id', $profile?->village_id) == $village->id)>
                                    {{ $village->display_name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Penghasilan Keluarga per Bulan</label>
                <input type="number" name="penghasilan_keluarga" min="0" class="form-input" value="{{ old('penghasilan_keluarga', $profile?->penghasilan_keluarga) }}">
            </div>
            <div>
                <label class="form-label">Jumlah Tanggungan Keluarga</label>
                <input type="number" name="jumlah_tanggungan" min="0" max="30" class="form-input" value="{{ old('jumlah_tanggungan', $profile?->jumlah_tanggungan) }}">
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" rows="3" class="form-input" required>{{ old('alamat', $profile?->alamat) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Prestasi Akademik/Nonakademik</label>
                <textarea name="prestasi" rows="3" class="form-input" placeholder="Informasi tambahan; tidak masuk dalam rumus skor otomatis.">{{ old('prestasi', $profile?->prestasi) }}</textarea>
            </div>
            </div>
        </fieldset>

        @if($application->canBeEditedByStudent())
            <div class="mt-7 flex justify-end">
                <button class="btn-primary">Simpan Data Mahasiswa</button>
            </div>
        @endif
    </form>
</section>

<section id="documents" class="card mt-7 p-6 sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                <x-icon name="upload" class="h-6 w-6" />
            </div>
            <div>
                <h2 class="text-xl font-bold">Unggah Dokumen Pendukung</h2>
                <p class="mt-1 text-sm text-slate-500">PDF/JPG/PNG, maksimal 2 MB per berkas. Dokumen khusus mengikuti jalur yang telah disimpan.</p>
            </div>
        </div>
        <div class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600">
            {{ $application->documentCompletionPercentage() }}% dokumen wajib
        </div>
    </div>

    <div class="mt-7 grid gap-5 lg:grid-cols-2">
        @foreach($documentTypes as $type)
            @php $document = $application->documents->firstWhere('document_type_id', $type->id); @endphp
            <div class="rounded-xl border {{ $document ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50/60' }} p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $document ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-brand-700' }}">
                        <x-icon :name="$document ? 'check' : 'document'" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold">{{ $type->name }}</h3>
                            @if($type->is_required)
                                <span class="rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700">WAJIB</span>
                            @endif
                            @if($type->application_type)
                                <span class="rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">{{ $type->application_type->label() }}</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">SEMUA JALUR</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $type->description }}</p>

                        @if($document)
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                <a href="{{ route('documents.download', $document) }}" class="font-semibold text-brand-600 hover:underline">
                                    {{ $document->original_name }}
                                </a>
                                <span class="text-slate-400">• {{ $document->human_size }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if($application->canBeEditedByStudent())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <form method="POST" action="{{ route('student.documents.store', $application) }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <input type="hidden" name="document_type_id" value="{{ $type->id }}">
                            <input type="file" name="file" class="block min-w-0 flex-1 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-xs file:font-semibold file:text-navy-900" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button class="btn-secondary !px-3 !py-2">{{ $document ? 'Ganti' : 'Unggah' }}</button>
                        </form>
                        @if($document)
                            <form method="POST" action="{{ route('student.documents.destroy', [$application, $document]) }}" class="mt-2 text-right" onsubmit="return confirm('Hapus dokumen ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-red-600 hover:underline">Hapus dokumen</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>

@if($application->canBeEditedByStudent())
    <section class="mt-7 flex flex-col items-start justify-between gap-4 rounded-xl bg-navy-900 p-6 text-white sm:flex-row sm:items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Konfirmasi Pengajuan</h2>
            <p class="mt-1 text-sm text-slate-300">Pastikan data dan dokumen benar sebelum dikirim.</p>
        </div>
        <form method="POST" action="{{ route('student.application.submit') }}" onsubmit="return confirm('Kirim pengajuan untuk diverifikasi?')">
            @csrf
            <button class="btn-blue">
                Kirim Pengajuan
                <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>
    </section>
@endif
@endsection
