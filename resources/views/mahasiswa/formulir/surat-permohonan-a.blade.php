<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Permohonan Beasiswa - Form A</title>

    <style>
        @page {
            margin: 3cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000000;
        }

        .page-break {
            page-break-after: always;
        }

        .page-header-num {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 6mm;
        }

        /*
        |--------------------------------------------------------------------------
        | HALAMAN 1 - COVER (- 11 -)
        |--------------------------------------------------------------------------
        */
        .cover-title {
            text-align: center;
            font-weight: bold;
            font-size: 13.5pt;
            line-height: 1.25;
            margin-top: 3mm;
            margin-bottom: 12mm;
        }

        .cover-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-table td {
            vertical-align: top;
            padding: 1.5mm 0;
            font-size: 11pt;
            line-height: 1.2;
        }

        .cover-label {
            width: 70mm;
            font-weight: normal;
        }

        .cover-colon {
            width: 4mm;
            text-align: left;
        }

        .cover-value {
            width: auto;
        }

        .cover-footer {
            text-align: center;
            font-size: 11pt;
            line-height: 1.35;
            margin-top: 30mm;
        }

        /*
        |--------------------------------------------------------------------------
        | HALAMAN 2 - SURAT PERMOHONAN (- 12 -)
        |--------------------------------------------------------------------------
        */
        .letter-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 3.5mm;
        }

        .letter-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .letter-header td {
            vertical-align: top;
            font-size: 12pt;
            line-height: 1.2;
        }

        .header-left {
            width: 52%;
        }

        .header-right {
            width: 48%;
            padding-left: 6mm;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 0.3mm 0;
            vertical-align: top;
            font-size: 12pt;
        }

        .meta-label {
            width: 18mm;
        }

        .meta-colon {
            width: 4mm;
            text-align: left;
        }

        .meta-value {
            width: auto;
        }

        .greeting {
            margin-top: 2mm;
            margin-bottom: 1.5mm;
            font-size: 12pt;
        }

        .identity-intro {
            margin-bottom: 1.5mm;
            font-size: 12pt;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        .identity-table td {
            vertical-align: top;
            padding: 0.3mm 0;
            font-size: 12pt;
            line-height: 1.15;
        }

        .id-no {
            width: 6mm;
        }

        .id-label {
            width: 48mm;
        }

        .id-colon {
            width: 4mm;
            text-align: left;
        }

        .id-val {
            width: auto;
        }

        .statement {
            text-align: justify;
            margin: 2mm 0 1.5mm 0;
            font-size: 12pt;
            line-height: 1.2;
        }

        .attachment-intro {
            text-align: justify;
            margin: 0 0 1.5mm 0;
            font-size: 12pt;
            line-height: 1.2;
        }

        .attachment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attachment-table td {
            vertical-align: top;
            padding: 0.35mm 0;
            font-size: 12pt;
            line-height: 1.2;
        }

        .att-no {
            width: 6mm;
        }

        .att-text {
            width: auto;
            text-align: justify;
        }

        /*
        |--------------------------------------------------------------------------
        | HALAMAN 3 - LANJUTAN & TANDA TANGAN (- 13 -)
        |--------------------------------------------------------------------------
        */
        .closing {
            margin-top: 3mm;
            text-align: justify;
            font-size: 12pt;
            line-height: 1.25;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10mm;
        }

        .signature-table td {
            vertical-align: top;
            font-size: 12pt;
        }

        .signature-left {
            width: 55%;
        }

        .signature-right {
            width: 45%;
            text-align: center;
        }

        .signature-title {
            margin-bottom: 0;
        }

        .signature-space {
            height: 25mm;
        }

        .signature-name {
            font-weight: normal;
        }

        .signature-nim {
            margin-top: 1mm;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

@php
    $dataPribadi = $pendaftaran->dataPribadi;
    $pendidikan = $pendaftaran->pendidikan;
    $orangTua = $pendaftaran->orangTua;

    /*
    |--------------------------------------------------------------------------
    | Helper data
    |--------------------------------------------------------------------------
    */
    $namaMahasiswa = $dataPribadi?->nama_lengkap ?? '-';
    $nim = $pendaftaran->user?->nim ?? ($pendidikan?->nim ?? '-');
    $jenjang = $pendidikan?->jenjang ?? '-';

    $akreditasi =
        $pendidikan?->akreditasi_perguruan_tinggi ??
        ($pendidikan?->akreditasi_universitas ?? ($pendidikan?->akreditasi ?? '-'));

    $akreditasiProgramStudi = $pendidikan?->akreditasi_program_studi ?? '-';
    $jurusan = $pendidikan?->jurusan ?? '-';
    $programStudi = $pendidikan?->program_studi ?? '-';
    $perguruanTinggi = $pendidikan?->universitas ?? '-';

    $statusPerguruanTinggi = $pendidikan?->status_perguruan_tinggi
        ? ucfirst(strtolower(trim($pendidikan->status_perguruan_tinggi)))
        : '-';

    $ipk = $pendidikan?->ipk !== null
        ? number_format((float) $pendidikan->ipk, 2)
        : ($pendidikan?->ipk ?? '-');

    $asalDesa = $dataPribadi?->desa ?? ($dataPribadi?->village?->name ?? '-');
    $alamatPerguruanTinggi = $pendidikan?->alamat_perguruan_tinggi ?? '-';
    $kategoriBeasiswa = $pendaftaran->kategoriBeasiswa?->nama ?? '-';
    $jenisBeasiswa = $pendaftaran->jalurBeasiswa?->nama ?? '-';

    $namaKetuaProdi = $pendidikan?->nama_ketua_prodi ?? '-';
    $namaKetuaJurusan = $pendidikan?->nama_ketua_jurusan ?? '-';
    $namaDirektur = $pendidikan?->nama_direktur ?? '-';
    $namaRektor = $pendidikan?->nama_rektor ?? '-';
    $noTelpPT = $pendidikan?->no_telp_pt ?? '-';

    $tahunMulaiKuliah = $pendidikan?->tahun_masuk ?? ($pendidikan?->tahun_mulai_kuliah ?? '-');
    $alamatMahasiswa = $dataPribadi?->alamat ?? '-';
    $telpMahasiswa = $dataPribadi?->no_hp ?? '-';

    $namaAyah = $orangTua?->nama_ayah ?? '-';
    $namaIbu = $orangTua?->nama_ibu ?? '-';

    $tanggalSurat = now()->translatedFormat('d F Y');
    $tahunAnggaran = $pendaftaran->periode?->year ?? ($pendaftaran->periode?->tahun ?? now()->year);
@endphp

<body>

    {{-- ================================================================
         HALAMAN 1 - COVER (- 11 -)
         ================================================================= --}}
    <div>
        <div class="cover-title">
            PERMOHONAN BEASISWA<br>
            MELALUI PROGRAM KARTU HEBAT MAHASISWA
        </div>

        <table class="cover-table">
            <tr>
                <td class="cover-label">NAMA MAHASISWA</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $namaMahasiswa }}</td>
            </tr>
            <tr>
                <td class="cover-label">NIM</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $nim }}</td>
            </tr>
            <tr>
                <td class="cover-label">JENJANG PROGRAM</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $jenjang }}</td>
            </tr>
            <tr>
                <td class="cover-label">AKREDITASI</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $akreditasi }}</td>
            </tr>
            <tr>
                <td class="cover-label">JURUSAN</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $jurusan }}</td>
            </tr>
            <tr>
                <td class="cover-label">PERGURUAN TINGGI</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $statusPerguruanTinggi }}</td>
            </tr>
            <tr>
                <td class="cover-label">INDEKS PRESTASI TERAKHIR</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $ipk }}</td>
            </tr>
            <tr>
                <td class="cover-label">ASAL DESA/KELURAHAN</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $asalDesa }}</td>
            </tr>
            <tr>
                <td class="cover-label">ALAMAT PERGURUAN TINGGI</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $alamatPerguruanTinggi }}</td>
            </tr>
            <tr>
                <td class="cover-label">KATEGORI BEASISWA</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $jenisBeasiswa }}</td>
            </tr>
            <tr>
                <td class="cover-label">JENIS BEASISWA</td>
                <td class="cover-colon">:</td>
                <td class="cover-value">{{ $kategoriBeasiswa }}</td>
            </tr>
        </table>

        <div class="cover-footer">
            Diajukan sebagai persyaratan permohonan untuk mendapatkan<br>
            Beasiswa Kartu Hebat Mahasiswa<br>
            Kabupaten Murung Raya<br>
            Tahun Anggaran {{ $tahunAnggaran }}
        </div>
    </div>

    <div class="page-break"></div>

    {{-- ================================================================
         HALAMAN 2 - SURAT PERMOHONAN + BIODATA + LAMPIRAN 1-9 (- 12 -)
         ================================================================= --}}
    <div>
        <div class="letter-title">
            SURAT PERMOHONAN
        </div>

        <table class="letter-header">
            <tr>
                {{-- KIRI --}}
                <td class="header-left">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Nomor</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-value">Lepas</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Sifat</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-value">Biasa</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Lampiran</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-value">1 (satu) berkas</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Perihal</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-value">Mohon Bantuan Biaya Pendidikan bagi Mahasiswa Tidak Mampu</td>
                        </tr>
                    </table>
                </td>

                {{-- KANAN --}}
                <td class="header-right">
                    <div style="text-align: right; margin-bottom: 2.5mm;">
                        Puruk Cahu, {{ $tanggalSurat }}
                    </div>
                    <div>Kepada</div>
                    <div>Yth. Bapak Bupati Murung Raya</div>
                    <div style="margin-left: 6mm;">di -</div>
                    <div style="margin-left: 12mm;">Tempat</div>
                </td>
            </tr>
        </table>

        <div class="greeting">
            Dengan Hormat,
        </div>

        <div class="identity-intro">
            Yang bertanda tangan di bawah ini :
        </div>

        {{-- DATA MAHASISWA (Flat Table agar titik dua sejajar rapi) --}}
        <table class="identity-table">
            <tr>
                <td class="id-no">1.</td>
                <td class="id-label">Nama Mahasiswa</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaMahasiswa }}</td>
            </tr>

            <tr>
                <td class="id-no">2.</td>
                <td class="id-label">N I M</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $nim }}</td>
            </tr>

            <tr>
                <td class="id-no">3.</td>
                <td class="id-label">IPK</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $ipk }}</td>
            </tr>

            <tr>
                <td class="id-no">4.</td>
                <td class="id-label">Jenjang Program</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $jenjang }}</td>
            </tr>

            <tr>
                <td class="id-no">5.</td>
                <td class="id-label">Program Studi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $programStudi }}</td>
            </tr>

            <tr>
                <td class="id-no">6.</td>
                <td class="id-label">Jurusan</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $jurusan }}</td>
            </tr>

            <tr>
                <td class="id-no">7.</td>
                <td class="id-label" colspan="3">Perguruan Tinggi</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">a. Nama Perguruan Tinggi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $perguruanTinggi }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">b. Status Perguruan Tinggi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $statusPerguruanTinggi }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">c. Akreditasi Program Studi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $akreditasiProgramStudi }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">d. Nama Ketua Prodi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaKetuaProdi }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">e. Nama Ketua Jurusan</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaKetuaJurusan }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">f. Nama Direktur</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaDirektur }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">g. Nama Rektor</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaRektor }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">h. Alamat Perguruan Tinggi</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $alamatPerguruanTinggi }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">i. No.Telp/HP PT</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $noTelpPT }}</td>
            </tr>

            <tr>
                <td class="id-no">8.</td>
                <td class="id-label">Tahun Mulai Kuliah</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $tahunMulaiKuliah }}</td>
            </tr>

            <tr>
                <td class="id-no">9.</td>
                <td class="id-label">Alamat Mahasiswa</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $alamatMahasiswa }}</td>
            </tr>

            <tr>
                <td class="id-no">10.</td>
                <td class="id-label">Telp/HP Mahasiswa</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $telpMahasiswa }}</td>
            </tr>

            <tr>
                <td class="id-no">11.</td>
                <td class="id-label" colspan="3">Nama</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">a. Ayah</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaAyah }}</td>
            </tr>

            <tr>
                <td class="id-no"></td>
                <td class="id-label" style="padding-left: 3mm;">b. Ibu</td>
                <td class="id-colon">:</td>
                <td class="id-val">{{ $namaIbu }}</td>
            </tr>
        </table>

        <div class="statement">
            Dengan ini mengajukan permohonan bantuan biaya pendidikan untuk keperluan perkuliahan.
        </div>

        <div class="attachment-intro">
            Sebagai bahan pertimbangan bagi Bapak Bupati, bersama ini saya lampirkan berkas administrasi sebagai berikut :
        </div>

        <table class="attachment-table">
            <tr>
                <td class="att-no">1.</td>
                <td class="att-text">Surat Keterangan Tidak Mampu dari Kepala Desa/Lurah yang diketahui oleh Camat;</td>
            </tr>
            <tr>
                <td class="att-no">2.</td>
                <td class="att-text">fotokopi Kartu Tanda Penduduk (KTP);</td>
            </tr>
            <tr>
                <td class="att-no">3.</td>
                <td class="att-text">fotokopi Kartu Keluarga (KK);</td>
            </tr>
            <tr>
                <td class="att-no">4.</td>
                <td class="att-text">fotokopi Kartu Mahasiswa yang masih berlaku dan dilegalisir oleh pejabat yang berwenang;</td>
            </tr>
            <tr>
                <td class="att-no">5.</td>
                <td class="att-text">Surat Keterangan Aktif Kuliah dari Perguruan Tinggi;</td>
            </tr>
            <tr>
                <td class="att-no">6.</td>
                <td class="att-text">fotokopi Kartu Rencana Studi (KRS) terakhir;</td>
            </tr>
            <tr>
                <td class="att-no">7.</td>
                <td class="att-text">fotokopi Kartu Hasil Studi (KHS) terakhir;</td>
            </tr>
            <tr>
                <td class="att-no">8.</td>
                <td class="att-text">fotokopi sertifikat akreditasi Perguruan Tinggi;</td>
            </tr>
            <tr>
                <td class="att-no">9.</td>
                <td class="att-text">fotokopi Buku Rekening Tabungan PT. Bank Pembangunan Daerah Kalimantan Tengah;</td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- ================================================================
         HALAMAN 3 - LAMPIRAN 10, PENUTUP & TANDA TANGAN (- 13 -)
         ================================================================= --}}
    <div>
        <table class="attachment-table">
            <tr>
                <td class="att-no">10.</td>
                <td class="att-text">pakta integritas yang dibubuhi materai Rp. 10.000,- (Sepuluh Ribu Rupiah).</td>
            </tr>
        </table>

        <div class="closing">
            Demikian Surat Permohonan ini diajukan, atas perhatian dan bantuan Bapak Bupati diucapkan terima kasih.
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-left"></td>
                <td class="signature-right">
                    <div class="signature-title">
                        Pemohon,
                    </div>
                    <div class="signature-space"></div>
                    <div class="signature-name">
                        {{ $namaMahasiswa }}
                    </div>
                    <div class="signature-nim">
                        NIM {{ $nim }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
