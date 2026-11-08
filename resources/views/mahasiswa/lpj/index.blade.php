@extends('layouts.portal')

@section('title', 'Laporan Pertanggungjawaban (LPJ)')
@section('header', 'Laporan Pertanggungjawaban')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER HALAMAN --}}
    <div>
        <p class="section-kicker">Kartu Hebat Mahasiswa</p>
        <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">
            Laporan Pertanggungjawaban (LPJ)
        </h1>
        <p class="mt-1 text-sm text-slate-600">
            Penyampaian bukti pembayaran UKT / SPP penggunaan bantuan beasiswa Kartu Hebat Mahasiswa.
        </p>
    </div>

    {{-- KONDISI 1: JIKA MAHASISWA SUDAH DINYATAKAN DITERIMA (PENERIMA BEASISWA) --}}
    @if($pendaftaran)

        {{-- ALERT STATUS KEWAJIBAN LPJ (JIKA SEDANG DITANGGUHKAN / PERLU PENYELESAIAN) --}}
        @if($isBlacklisted || ($isPastDeadline && (! $lpj || $lpj->status !== 'disetujui')))
            <div class="rounded-2xl border-2 border-rose-300 bg-rose-50 p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-200 text-rose-900">
                        <x-icon name="exclamation-triangle" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-rose-200 px-2.5 py-0.5 text-[11px] font-extrabold text-rose-900 uppercase tracking-wide">
                                🚫 Akun Terkunci
                            </span>
                            <h3 class="text-base font-bold text-rose-950">
                                Pendaftaran Periode Selanjutnya Ditangguhkan (Kewajiban Laporan Pertanggungjawaban (LPJ) Belum Selesai)
                            </h3>
                        </div>
                        <p class="mt-2 text-sm text-rose-900 leading-relaxed font-medium">
                            <strong>Perhatian:</strong> Selama status LPJ Anda belum berstatus <strong>"Disetujui"</strong>, sistem secara otomatis menangguhkan NIK Anda sehingga Anda <strong>tidak dapat mengajukan pendaftaran beasiswa di seluruh periode berikutnya</strong>.
                        </p>
                        @if($activeBlacklist?->alasan)
                            <div class="mt-2.5 rounded-lg bg-white/90 border border-rose-200 p-2.5 text-xs text-rose-950">
                                <span class="font-bold text-rose-900">Catatan Admin:</span>
                                <span class="italic text-slate-700">{{ $activeBlacklist->alasan }}</span>
                            </div>
                        @endif
                        @if($lpj && $lpj->status === 'menunggu_verifikasi')
                            <p class="mt-2 text-xs font-semibold text-rose-950">
                                Berkas LPJ susulan Anda telah terkirim ke sistem. Mohon segera melapor diri ke <strong>Bagian Kesejahteraan Rakyat (Kesra) Sekretariat Daerah Kabupaten Murung Raya</strong> agar berkas dapat diverifikasi dan blokir akun pendaftaran Anda dibuka.
                            </p>
                        @else
                            <p class="mt-2 text-xs font-semibold text-rose-950">
                                Silakan unggah berkas bukti pembayaran UKT / LPJ yang valid di bawah ini. Setelah berhasil mengunggah, silakan melapor diri ke <strong>Bagian Kesejahteraan Rakyat (Kesra) Sekretariat Daerah Kabupaten Murung Raya</strong> untuk verifikasi berkas dan pembukaan blokir akun pendaftaran Anda.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- KARTU STATUS PENERIMA & JADWAL LPJ --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                        <x-icon name="check" class="h-3.5 w-3.5" />
                        Penerima Beasiswa Periode {{ $periode?->tahun ?? '-' }}
                    </span>
                    <h2 class="mt-2 text-lg font-extrabold text-navy-950">
                        {{ $pendaftaran->dataPribadi?->nama_lengkap ?? auth()->user()->name }}
                    </h2>
                    <p class="text-xs text-slate-500">
                        {{ $pendaftaran->nomor_pendaftaran }} · {{ $pendaftaran->kategoriBeasiswa?->nama }}
                    </p>
                </div>

                {{-- STATUS VERIFIKASI BERKAS LPJ --}}
                <div class="sm:text-right">
                    <p class="text-xs font-medium text-slate-500">Status Berkas LPJ:</p>
                    <div class="mt-1">
                        @if(! $lpj || $lpj->status === 'belum_upload')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                Belum Mengumpulkan
                            </span>
                        @elseif($lpj->status === 'menunggu_verifikasi')
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-800">
                                Menunggu Verifikasi
                            </span>
                        @elseif($lpj->status === 'disetujui')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                Disetujui
                            </span>
                        @elseif($lpj->status === 'ditolak')
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">
                                Perlu Perbaikan
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- KETERANGAN JADWAL --}}
            <div class="mt-5 grid gap-4 sm:grid-cols-2 text-xs">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-medium text-slate-500">Jadwal Pengumpulan LPJ:</p>
                    @if($periode && $periode->tanggal_mulai_lpj && $periode->tanggal_selesai_lpj)
                        <p class="mt-1 font-bold text-slate-800">
                            {{ $periode->tanggal_mulai_lpj->format('d M Y') }} s/d {{ $periode->tanggal_selesai_lpj->format('d M Y') }}
                        </p>
                        @if($lpj && $lpj->status === 'disetujui')
                            <p class="mt-0.5 text-emerald-600 font-semibold">● Kewajiban LPJ Selesai (Berkas Disetujui)</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">Anda telah menyelesaikan kewajiban LPJ periode ini dan bebas mendaftar di periode berikutnya.</p>
                        @elseif($lpj && $lpj->status === 'menunggu_verifikasi')
                            @if($isPastDeadline || $isBlacklisted)
                                <p class="mt-0.5 text-purple-600 font-semibold">● Berkas LPJ Susulan Telah Dikirim — Menunggu Verifikasi</p>
                                <p class="mt-0.5 text-[11px] text-purple-900 font-medium">Berkas telah terkirim ke sistem. Mohon segera melapor diri ke Bagian Kesejahteraan Rakyat (Kesra) Sekretariat Daerah Kabupaten Murung Raya untuk proses verifikasi dan pembukaan blokir akun.</p>
                            @else
                                <p class="mt-0.5 text-purple-600 font-semibold">● Berkas LPJ Telah Dikirim — Menunggu Verifikasi Admin</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">Bukti pembayaran UKT Anda sedang dalam antrean pemeriksaan oleh Admin Kabupaten.</p>
                            @endif
                        @elseif($lpj && $lpj->status === 'ditolak')
                            <p class="mt-0.5 text-rose-600 font-semibold">● Berkas Perlu Perbaikan — Wajib Unggah Ulang Berkas yang Sesuai</p>
                        @elseif($isLpjOpen)
                            <p class="mt-0.5 text-emerald-600 font-semibold">● Pengumpulan Sedang Dibuka — Silakan Unggah Bukti UKT Anda</p>
                        @elseif($isPastDeadline)
                            <p class="mt-0.5 text-rose-600 font-semibold">● Telah Lewat Batas Waktu — Segera Kumpulkan agar Bisa Mendaftar di Periode Selanjutnya</p>
                        @else
                            <p class="mt-0.5 text-amber-600 font-semibold">● Pengumpulan Belum Dimulai</p>
                        @endif
                    @else
                        <p class="mt-1 text-slate-700">Jadwal pengumpulan akan segera ditetapkan oleh pengelola program.</p>
                    @endif
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-medium text-slate-500">Perguruan Tinggi:</p>
                    <p class="mt-1 font-bold text-slate-800">
                        {{ $pendaftaran->pendidikan?->universitas ?? '-' }}
                    </p>
                    <p class="text-slate-600">
                        {{ $pendaftaran->pendidikan?->program_studi ?? '-' }} (Sem {{ $pendaftaran->pendidikan?->semester ?? '-' }})
                    </p>
                </div>
            </div>

            {{-- CATATAN VERIFIKASI (JIKA PERLU PERBAIKAN) --}}
            @if($lpj && $lpj->status === 'ditolak' && $lpj->catatan)
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
                    <p class="font-bold">Catatan Perbaikan dari Admin Kabupaten:</p>
                    <p class="mt-1 text-slate-700">{{ $lpj->catatan }}</p>
                </div>
            @endif
        </div>

        {{-- JIKA SUDAH PERNAH UPLOAD BERKAS --}}
        @if($lpj && $lpj->file_path)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-bold text-navy-950">Berkas LPJ yang Telah Diunggah</h3>
                <div class="mt-3 flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                            <x-icon name="document" class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 truncate max-w-md">
                                {{ $lpj->original_name ?: 'Bukti-Pembayaran-UKT.pdf' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Ukuran: {{ $lpj->size ? number_format($lpj->size / 1024, 1) . ' KB' : '-' }} ·
                                Diunggah: {{ $lpj->uploaded_at ? $lpj->uploaded_at->format('d M Y, H:i') : '-' }} WIB
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('mahasiswa.lpj.file') }}" target="_blank" class="btn-secondary text-xs shrink-0 inline-flex items-center gap-1.5">
                        <x-icon name="document" class="h-4 w-4" />
                        Lihat Berkas
                    </a>
                </div>
            </div>
        @endif

        {{-- FORM UPLOAD / UPDATE LPJ --}}
        @if($canUpload)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-bold text-navy-950">
                    {{ $lpj && $lpj->file_path ? 'Unggah Ulang Berkas LPJ' : 'Unggah Bukti Pembayaran UKT / LPJ' }}
                </h3>
                <p class="mt-1 text-xs text-slate-600">
                    Unggah bukti pembayaran UKT atau kuitansi resmi dari perguruan tinggi Anda.
                </p>

                <form method="POST" action="{{ route('mahasiswa.lpj.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="file" class="form-label text-xs">Pilih Berkas Bukti Pembayaran <span class="text-red-500">*</span></label>
                        <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-input text-xs" required>
                        <p class="mt-1.5 text-xs text-slate-500">
                            Format berkas: <strong>PDF, JPG, JPEG, PNG</strong>. Ukuran maksimal: <strong>2 MB (2.048 KB)</strong>.
                        </p>
                        @error('file')
                            <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-primary text-xs">
                            {{ $lpj && $lpj->file_path ? 'Perbarui Berkas LPJ' : 'Kirim Laporan Pertanggungjawaban' }}
                        </button>
                    </div>
                </form>
            </div>
        @elseif($lpj && $lpj->status === 'disetujui')
            {{-- INFORMASI JIKA LPJ SUDAH DISETUJUI --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800">
                        <x-icon name="check" class="h-6 w-6" />
                    </span>
                    <div>
                        <h4 class="text-base font-bold text-emerald-950">Laporan Pertanggungjawaban Telah Selesai & Disetujui</h4>
                        <p class="mt-1 text-xs text-emerald-900 leading-relaxed">
                            Terima kasih! Bukti pembayaran UKT Anda untuk periode ini telah diverifikasi dan disahkan oleh Admin Kabupaten. Akun Anda bebas dari kewajiban LPJ dan siap untuk mendaftar pada periode beasiswa berikutnya.
                        </p>
                        @if($lpj->catatan)
                            <p class="mt-2.5 text-xs font-medium text-emerald-800 italic bg-white/70 p-2.5 rounded-lg border border-emerald-200">
                                Catatan Admin: "{{ $lpj->catatan }}"
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- INFORMASI JIKA JADWAL LPJ BELUM DIBUKA --}}
            <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-6 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                    <x-icon name="clock" class="h-6 w-6" />
                </div>
                <h3 class="mt-3 text-base font-bold text-blue-950">
                    Pengumpulan LPJ Belum Dibuka
                </h3>
                <p class="mx-auto mt-2 max-w-lg text-xs leading-relaxed text-blue-800">
                    Jadwal pengunggahan berkas Laporan Pertanggungjawaban akan dibuka secara resmi setelah proses pencairan dana beasiswa ke rekening penerima selesai disalurkan. Silakan pantau halaman ini secara berkala.
                </p>
            </div>
        @endif

    {{-- KONDISI 2: JIKA MAHASISWA BELUM DINYATAKAN DITERIMA (PENDAFTARAN / SELEKSI SEDANG BERJALAN) --}}
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                <x-icon name="folder" class="h-7 w-7" />
            </div>

            <h3 class="mt-4 text-lg font-bold text-navy-950">
                Pengumpulan LPJ Belum Aktif
            </h3>

            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                Saat ini tahapan pendaftaran dan seleksi beasiswa sedang berlangsung. Menu Laporan Pertanggungjawaban (LPJ) hanya diperuntukkan bagi mahasiswa yang telah dinyatakan <strong>Lulus / Diterima</strong> sebagai penerima beasiswa dan telah menerima pencairan dana bantuan.
            </p>

            @if($latestPendaftaran)
                <div class="mt-6 inline-flex flex-col items-center rounded-xl bg-slate-50 px-5 py-3 text-xs">
                    <span class="font-medium text-slate-500">Status Pengajuan Anda Saat Ini:</span>
                    <span class="mt-1 font-bold text-slate-800">
                        {{ $latestPendaftaran->nomor_pendaftaran }} ({{ ucfirst(str_replace('_', ' ', $latestPendaftaran->status)) }})
                    </span>
                </div>
            @else
                <div class="mt-6">
                    <a href="{{ route('mahasiswa.pendaftaran.create') }}" class="btn-primary text-xs">
                        Buat Pendaftaran Beasiswa
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- KOTAK INFORMASI BERKAS PERSIAPAN (SESUAI KESEPAKATAN DISKUSI) --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-navy-950">Dokumen yang Perlu Disiapkan</h3>
        <p class="mt-1 text-xs text-slate-500">
            Panduan persiapan dokumen untuk pengumpulan Laporan Pertanggungjawaban:
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <x-icon name="document" class="h-4 w-4" />
                </div>
                <div class="text-xs">
                    <p class="font-bold text-slate-800">Bukti Pembayaran UKT / SPP Asli</p>
                    <p class="mt-0.5 text-slate-500">Bukti setoran bank atau kuitansi resmi dari kampus yang terbaca jelas.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                    <x-icon name="check" class="h-4 w-4" />
                </div>
                <div class="text-xs">
                    <p class="font-bold text-slate-800">Format & Batas Ukuran</p>
                    <p class="mt-0.5 text-slate-500">Format file <strong>PDF atau Foto (JPG, JPEG, PNG)</strong> dengan ukuran maksimal <strong>2 MB (2.048 KB)</strong>.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PENDAFTARAN & LPJ PERIODE SEBELUMNYA --}}
    @if(isset($riwayatPendaftarans) && $riwayatPendaftarans->count() > 0)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-navy-950">Riwayat Berkas LPJ & Pendaftaran Sebelumnya</h3>
                <p class="mt-0.5 text-xs text-slate-500">Arsip riwayat pengumpulan berkas LPJ dan pendaftaran beasiswa Anda.</p>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>No. Pendaftaran</th>
                            <th>Kategori</th>
                            <th>Status LPJ</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayatPendaftarans as $riwayat)
                            @php
                                $rLpj = $riwayat->laporanPertanggungjawaban;
                                $isApproved = $riwayat->status === 'approved' || ($riwayat->application && $riwayat->application->status === \App\Enums\ApplicationStatus::DITERIMA);
                            @endphp
                            <tr>
                                <td class="font-bold text-slate-800">
                                    {{ $riwayat->periode?->tahun ?? '-' }}
                                    <span class="block text-[11px] font-normal text-slate-500">{{ $riwayat->periode?->nama }}</span>
                                </td>
                                <td class="font-mono text-xs">{{ $riwayat->nomor_pendaftaran }}</td>
                                <td>
                                    <p class="text-xs font-semibold text-slate-800">{{ $riwayat->jalurBeasiswa?->nama ?? '-' }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $riwayat->kategoriBeasiswa?->nama ?? '-' }}</p>
                                </td>
                                <td>
                                    @if(! $isApproved)
                                        <span class="text-xs text-slate-400">Bukan Penerima</span>
                                    @elseif(! $rLpj || $rLpj->status === 'belum_upload')
                                        <span class="status-chip status-warning text-xs">Belum Upload</span>
                                    @elseif($rLpj->status === 'menunggu_verifikasi')
                                        <span class="status-chip status-purple text-xs">Menunggu Verifikasi</span>
                                    @elseif($rLpj->status === 'disetujui')
                                        <span class="status-chip status-success text-xs">Disetujui</span>
                                    @elseif($rLpj->status === 'ditolak')
                                        <span class="status-chip status-danger text-xs">Perlu Perbaikan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rLpj && $rLpj->file_path)
                                        <a href="{{ route('mahasiswa.lpj.file', ['pendaftaran_id' => $riwayat->id]) }}" target="_blank" class="btn-secondary !py-1 !px-2.5 text-xs">
                                            <x-icon name="document" class="h-3.5 w-3.5" />
                                            Lihat Berkas LPJ
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

</div>
@endsection
