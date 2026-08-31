<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Surat Permohonan Beasiswa</title>

    <style>
        @page {
            size: 215.9mm 330.2mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.25;
            color: #000000;
        }

        .page {
            width: 215.9mm;
            height: 330.2mm;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            background: #ffffff;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /*
        |--------------------------------------------------------------------------
        | Margin surat
        |--------------------------------------------------------------------------
        */

        .content {
            position: absolute;
            top: 25mm;
            left: 25mm;
            right: 25mm;
            bottom: 20mm;
        }

        /*
        |--------------------------------------------------------------------------
        | HALAMAN 1
        |--------------------------------------------------------------------------
        */

        .cover {
            padding-top: 0;
        }

        .cover-title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            line-height: 1.15;
            margin-bottom: 25mm;
        }

        .cover-biodata {
            position: absolute;
            top: 30mm;
            left: 7mm;
            width: 145mm;
        }

        .bio-row {
            position: relative;
            height: 6.3mm;
            font-size: 12pt;
            line-height: 6.3mm;
        }

        .bio-label {
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
            white-space: nowrap;
        }

        .bio-colon {
            position: absolute;
            left: 80mm;
            top: 0;
            width: 3mm;
        }

        .bio-value {
            position: absolute;
            left: 85mm;
            top: 0;
            width: 60mm;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .cover-footer {
            position: absolute;
            left: 25mm;
            right: 25mm;
            top: 129mm;
            text-align: center;
            font-size: 12pt;
            line-height: 1.4;
        }

        /*
        |--------------------------------------------------------------------------
        | HALAMAN 2 DAN 3 - SURAT
        |--------------------------------------------------------------------------
        */

        .letter-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 5mm 0;
        }

        .letter-date {
            width: 44%;
            margin-left: 56%;
            padding-left: 4mm;
            box-sizing: border-box;
            text-align: left;
            margin-bottom: 4mm;
        }

        /*
        |--------------------------------------------------------------------------
        | Bagian nomor dan kepada
        |--------------------------------------------------------------------------
        */

        .letter-header {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8mm;
        }

        .letter-header td {
            vertical-align: top;
            font-size: 12pt;
            line-height: 1.25;
        }

        .header-left {
            width: 56%;
            padding-right: 8mm;
        }

        .header-right {
            width: 44%;
            padding-left: 4mm;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 0;
            vertical-align: top;
        }

        .meta-label {
            width: 25mm;
        }

        .meta-colon {
            width: 7mm;
            text-align: center;
        }

        .meta-value {
            width: auto;
        }

        .kepada {
            text-align: left;
        }

        .kepada-title {
            margin-bottom: 1mm;
        }

        .kepada-name {
            white-space: nowrap;
        }

        .kepada-place {
            margin-top: 1mm;
        }

        /*
        |--------------------------------------------------------------------------
        | Isi surat
        |--------------------------------------------------------------------------
        */

        .paragraph {
            text-align: justify;
            margin: 0 0 5mm 0;
        }

        .greeting {
            margin-bottom: 7mm;
        }

        /* ================================================================
        DATA MAHASISWA - HALAMAN 2
        ================================================================ */

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .identity-table td {
            vertical-align: top;
            padding: 0 0 1.2mm 0;
            font-size: 11pt;
            line-height: 1.15;
        }

        .identity-no {
            width: 8mm;
            padding: 0 !important;
            white-space: nowrap;
        }

        .identity-label {
            width: 48mm;
            padding: 0 1mm 0 0 !important;
        }

        .identity-colon {
            width: 5mm;
            text-align: left;
            padding: 0 !important;
        }

        .identity-value {
            width: auto;
            padding: 0 !important;
        }


        /* ================================================================
        SUB DATA PERGURUAN TINGGI
        ================================================================ */

        .sub-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .sub-table td {
            vertical-align: top;
            padding: 0 0 1mm 0;
            font-size: 11pt;
            line-height: 1.15;
        }

        .sub-letter {
            width: 7mm;
            padding: 0 !important;
            white-space: nowrap;
        }

        .sub-label {
            width: 48mm;
            padding: 0 1mm 0 0 !important;
        }

        .sub-colon {
            width: 5mm;
            padding: 0 !important;
            text-align: left;
        }

        .sub-value {
            width: auto;
            padding: 0 !important;
            overflow-wrap: normal;
            word-break: normal;
        }


        /* Jangan paksa setiap baris pecah */
        .identity-table {
            page-break-inside: auto;
        }

        .identity-table tr {
            page-break-inside: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | Penutup
        |--------------------------------------------------------------------------
        */

        .statement {
            width: 145mm;
            margin: 2mm auto 2mm;
            text-align: justify;
        }

        .attachment-intro {
            width: 145mm;
            margin: 0 auto 1mm;
            text-align: justify;
        }

        /*
        |--------------------------------------------------------------------------
        | Daftar lampiran
        |--------------------------------------------------------------------------
        */

        .attachment-table {
            width: 145mm;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .attachment-table td {
            vertical-align: top;
            padding: 0 0 0.5mm 0;
            line-height: 1.1;
        }

        .attachment-no {
            width: 12mm;
        }

        .attachment-text {
            width: auto;
            text-align: left;
        }

        /* ================================================================
   HALAMAN 3
   ================================================================= */

        .page-three {
            padding-top: 20mm;
        }


        /* ================================================================
   PERNYATAAN
   ================================================================= */

        .statement {
            text-align: justify;
            margin: 0 0 4mm 0;
            font-size: 11pt;
            line-height: 1.25;
        }


        /* ================================================================
   PEMBUKA LAMPIRAN
   ================================================================= */

        .attachment-intro {
            text-align: justify;
            margin: 0 0 2mm 0;
            font-size: 11pt;
            line-height: 1.25;
        }


        /* ================================================================
   LAMPIRAN
   ================================================================= */

        .attachment-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .attachment-table td {
            vertical-align: top;
            padding: 0 0 1.5mm 0;
            font-size: 11pt;
            line-height: 1.2;
        }

        .attachment-no {
            width: 8mm;
            padding-right: 1mm !important;
        }

        .attachment-text {
            width: auto;
            text-align: left;
        }


        /* ================================================================
   PENUTUP
   ================================================================= */

        .closing {
            margin-top: 5mm;
            text-align: justify;
            font-size: 11pt;
            line-height: 1.25;
        }


        /* ================================================================
   TANDA TANGAN
   ================================================================= */

        .signature {
            width: 55mm;
            margin-left: auto;
            margin-top: 10mm;
            text-align: center;
            font-size: 11pt;
        }

        .signature-title {
            margin-bottom: 0;
        }

        .signature-name {
            text-align: center;
            font-weight: normal;
        }

        .signature-nim {
            text-align: center;
            margin-top: 1mm;
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan biarkan tabel pecah
        |--------------------------------------------------------------------------
        */

        table {
            page-break-inside: avoid;
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

    $ipk = $pendidikan?->ipk ?? '-';

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

    /*
    |--------------------------------------------------------------------------
    | Tanggal
    |--------------------------------------------------------------------------
    */

    $tanggalSurat = now()->translatedFormat('d F Y');

    /*
    |--------------------------------------------------------------------------
    | Tahun anggaran
    |--------------------------------------------------------------------------
    */

    $tahunAnggaran = $pendaftaran->periode?->year ?? now()->year;
@endphp

<body>

    {{-- ================================================================
         HALAMAN 1
         ================================================================= --}}
    <div class="page">

        <div class="content cover">

            <div class="cover-title">
                PERMOHONAN BEASISWA<br>
                MELALUI PROGRAM KARTU HEBAT MAHASISWA
            </div>

            <div class="cover-biodata">

                <div class="bio-row">
                    <span class="bio-label">NAMA MAHASISWA</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $namaMahasiswa }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">NIM</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $nim }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">JENJANG PROGRAM</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $jenjang }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">AKREDITASI</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $akreditasi }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">JURUSAN</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $jurusan }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">PERGURUAN TINGGI</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $statusPerguruanTinggi }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">INDEKS PRESTASI TERAKHIR</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $ipk }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">ASAL DESA/KELURAHAN</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $asalDesa }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">ALAMAT PERGURUAN TINGGI</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $alamatPerguruanTinggi }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">KATEGORI BEASISWA</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $jenisBeasiswa }}</span>
                </div>

                <div class="bio-row">
                    <span class="bio-label">JENIS BEASISWA</span>
                    <span class="bio-colon">:</span>
                    <span class="bio-value">{{ $kategoriBeasiswa }}</span>
                </div>

            </div>

            <div class="cover-footer">

                Diajukan sebagai persyaratan permohonan untuk mendapatkan<br>

                Beasiswa Kartu Hebat Mahasiswa<br>

                Kabupaten Murung Raya<br>

                Tahun Anggaran {{ $tahunAnggaran }}

            </div>

        </div>

    </div>


    {{-- ================================================================
     HALAMAN 2 - SURAT PERMOHONAN + BIODATA
     ================================================================= --}}
    <div class="page">

        <div class="content">

            {{-- JUDUL --}}
            <div class="letter-title">
                SURAT PERMOHONAN
            </div>

            {{-- TANGGAL --}}
            <div class="letter-date">
                Puruk Cahu, {{ $tanggalSurat }}
            </div>


            {{-- ========================================================
             HEADER SURAT
             ======================================================== --}}
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
                                <td class="meta-value">
                                    Mohon Bantuan Biaya Pendidikan bagi Mahasiswa Tidak Mampu
                                </td>
                            </tr>

                        </table>

                    </td>


                    {{-- KANAN --}}
                    <td class="header-right">

                        <div class="kepada">

                            <div class="kepada-title">
                                Kepada
                            </div>

                            <div class="kepada-name">
                                Yth. Bapak Bupati Murung Raya
                            </div>

                            <div class="kepada-place">
                                di -
                            </div>

                            <div>
                                Tempat
                            </div>

                        </div>

                    </td>

                </tr>

            </table>


            {{-- ========================================================
             PEMBUKA
             ======================================================== --}}

            <div class="greeting">
                Dengan Hormat,
            </div>

            <div class="identity-intro">
                Yang bertanda tangan di bawah ini :
            </div>


            {{-- ========================================================
             DATA MAHASISWA
             ======================================================== --}}

            <table class="identity-table">

                <tr>
                    <td class="identity-no">1.</td>
                    <td class="identity-label">Nama Mahasiswa</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $namaMahasiswa }}
                    </td>
                </tr>

                <tr>
                    <td class="identity-no">2.</td>
                    <td class="identity-label">N I M</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $nim }}
                    </td>
                </tr>

                <tr>
                    <td class="identity-no">3.</td>
                    <td class="identity-label">IPK</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $ipk }}
                    </td>
                </tr>

                <tr>
                    <td class="identity-no">4.</td>
                    <td class="identity-label">Jenjang Program</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $jenjang }}
                    </td>
                </tr>

                <tr>
                    <td class="identity-no">5.</td>
                    <td class="identity-label">Program Studi</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $programStudi }}
                    </td>
                </tr>

                <tr>
                    <td class="identity-no">6.</td>
                    <td class="identity-label">Jurusan</td>
                    <td class="identity-colon">:</td>
                    <td class="identity-value">
                        {{ $jurusan }}
                    </td>
                </tr>


                {{-- PERGURUAN TINGGI --}}
                <tr>
                    <td class="identity-no">7.</td>

                    <td class="identity-label">
                        Perguruan Tinggi
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">

                        <table class="sub-table">

                            <tr>
                                <td class="sub-letter">a.</td>

                                <td class="sub-label">
                                    Nama Perguruan Tinggi
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $perguruanTinggi }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">b.</td>

                                <td class="sub-label">
                                    Status Perguruan Tinggi
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $statusPerguruanTinggi }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">c.</td>

                                <td class="sub-label">
                                    Akreditasi Program Studi
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $akreditasiProgramStudi }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">d.</td>

                                <td class="sub-label">
                                    Nama Ketua Prodi
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $namaKetuaProdi }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">e.</td>

                                <td class="sub-label">
                                    Nama Ketua Jurusan
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $namaKetuaJurusan }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">f.</td>

                                <td class="sub-label">
                                    Nama Direktur
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $namaDirektur }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">g.</td>

                                <td class="sub-label">
                                    Nama Rektor
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $namaRektor }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">h.</td>

                                <td class="sub-label">
                                    Alamat Perguruan Tinggi
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $alamatPerguruanTinggi }}
                                </td>
                            </tr>

                            <tr>
                                <td class="sub-letter">i.</td>

                                <td class="sub-label">
                                    No.Telp/HP PT
                                </td>

                                <td class="sub-colon">
                                    :
                                </td>

                                <td class="sub-value">
                                    {{ $noTelpPT }}
                                </td>
                            </tr>

                        </table>

                    </td>
                </tr>


                <tr>
                    <td class="identity-no">8.</td>

                    <td class="identity-label">
                        Tahun Mulai Kuliah
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                        {{ $tahunMulaiKuliah }}
                    </td>
                </tr>


                <tr>
                    <td class="identity-no">9.</td>

                    <td class="identity-label">
                        Alamat Mahasiswa
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                        {{ $alamatMahasiswa }}
                    </td>
                </tr>


                <tr>
                    <td class="identity-no">10.</td>

                    <td class="identity-label">
                        Telp/HP Mahasiswa
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                        {{ $telpMahasiswa }}
                    </td>
                </tr>


                <tr>
                    <td class="identity-no">11.</td>

                    <td class="identity-label">
                        Nama
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                    </td>
                </tr>


                <tr>
                    <td></td>

                    <td class="identity-label">
                        <span style="padding-left: 8mm;">
                            a. Ayah
                        </span>
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                        {{ $namaAyah }}
                    </td>
                </tr>


                <tr>
                    <td></td>

                    <td class="identity-label">
                        <span style="padding-left: 8mm;">
                            b. Ibu
                        </span>
                    </td>

                    <td class="identity-colon">
                        :
                    </td>

                    <td class="identity-value">
                        {{ $namaIbu }}
                    </td>
                </tr>

            </table>

        </div>

    </div>


    {{-- ================================================================
     HALAMAN 3 - PERNYATAAN, LAMPIRAN DAN TANDA TANGAN
     ================================================================= --}}
    <div class="page">

        <div class="content page-three">

            {{-- ========================================================
             PERNYATAAN
             ======================================================== --}}

            <div class="statement">

                Dengan ini mengajukan permohonan bantuan biaya pendidikan
                untuk keperluan perkuliahan.

            </div>


            {{-- ========================================================
             PEMBUKA LAMPIRAN
             ======================================================== --}}

            <div class="attachment-intro">

                Sebagai bahan pertimbangan bagi Bapak Bupati, bersama ini
                saya lampirkan berkas administrasi sebagai berikut :

            </div>


            {{-- ========================================================
             DAFTAR LAMPIRAN
             ======================================================== --}}

            <table class="attachment-table">

                <tr>
                    <td class="attachment-no">1.</td>
                    <td class="attachment-text">
                        Surat Keterangan Tidak Mampu dari Kepala Desa/Lurah
                        yang diketahui oleh Camat;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">2.</td>
                    <td class="attachment-text">
                        fotokopi Kartu Tanda Penduduk (KTP);
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">3.</td>
                    <td class="attachment-text">
                        fotokopi Kartu Keluarga (KK);
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">4.</td>
                    <td class="attachment-text">
                        fotokopi Kartu Mahasiswa yang masih berlaku dan
                        dilegalisir oleh pejabat yang berwenang;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">5.</td>
                    <td class="attachment-text">
                        Surat Keterangan Aktif Kuliah dari Perguruan Tinggi;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">6.</td>
                    <td class="attachment-text">
                        fotokopi Kartu Rencana Studi (KRS) terakhir;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">7.</td>
                    <td class="attachment-text">
                        fotokopi Kartu Hasil Studi (KHS) terakhir;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">8.</td>
                    <td class="attachment-text">
                        fotokopi sertifikat akreditasi Perguruan Tinggi;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">9.</td>
                    <td class="attachment-text">
                        fotokopi Buku Rekening Tabungan PT. Bank Pembangunan
                        Daerah Kalimantan Tengah;
                    </td>
                </tr>

                <tr>
                    <td class="attachment-no">10.</td>
                    <td class="attachment-text">
                        pakta integritas yang dibubuhi materai Rp. 10.000,-
                        (Sepuluh Ribu Rupiah).
                    </td>
                </tr>

            </table>


            {{-- ========================================================
             PENUTUP
             ======================================================== --}}

            <div class="closing">

                Demikian Surat Permohonan ini diajukan, atas perhatian dan
                bantuan Bapak Bupati diucapkan terima kasih.

            </div>


            {{-- ========================================================
             TANDA TANGAN
             ======================================================== --}}

            <div class="signature">

                <div class="signature-title">
                    Pemohon,
                </div>

                <div style="height: 25mm;"></div>

                <div class="signature-name">
                    {{ $namaMahasiswa }}
                </div>

                <div class="signature-nim">
                    NIM {{ $nim }}
                </div>

            </div>
        </div>

    </div>

</body>

</html>
