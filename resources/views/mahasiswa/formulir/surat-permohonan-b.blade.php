<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Permohonan Beasiswa - Form B
    </title>

    <style>
        @page {
            size: A4 portrait;
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
            color: #000000;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
        }

        .page {
            position: relative;
            width: 210mm;
            min-height: 297mm;

            padding-top: 18mm;
            padding-right: 20mm;
            padding-bottom: 18mm;
            padding-left: 20mm;

            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .page-number {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 15mm;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            line-height: 1.25;
            margin-bottom: 10mm;
        }

        .subtitle {
            text-align: center;
            font-size: 12pt;
            line-height: 1.35;
            margin-bottom: 2mm;
        }

        .identitas-awal {
            width: 100%;
            margin-top: 15mm;
            margin-left: 5mm;
        }

        .identitas-awal td {
            vertical-align: top;
            padding: 1px 0;
            line-height: 1.25;
        }

        .identitas-awal .label {
            width: 70mm;
        }

        .identitas-awal .colon {
            width: 7mm;
            text-align: center;
        }

        .identitas-awal .value {
            width: auto;
        }

        .penutup-halaman-1 {
            text-align: center;
            margin-top: 14mm;
            line-height: 1.35;
        }

        .surat-title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 8mm;
        }

        .tanggal {
            text-align: center;
            margin-bottom: 8mm;
        }

        .surat-meta {
            width: 100%;
            margin-bottom: 12mm;
        }

        .surat-meta td {
            vertical-align: top;
            padding: 0;
            line-height: 1.2;
        }

        .surat-meta .label {
            width: 22mm;
        }

        .surat-meta .colon {
            width: 7mm;
            text-align: center;
        }

        .surat-meta .value {
            width: 67mm;
        }

        .kepada {
            vertical-align: top;
        }

        .kepada-content {
            width: 70mm;
            line-height: 1.2;
        }

        .center {
            text-align: center;
        }

        .isi-surat {
            margin-top: 5mm;
            line-height: 1.25;
            text-align: justify;
        }

        .isi-surat p {
            margin: 0 0 5mm 0;
        }

        .data-lengkap {
            width: 100%;
            margin-top: 3mm;
            margin-bottom: 6mm;
        }

        .data-lengkap td {
            vertical-align: top;
            padding: 0;
            line-height: 1.18;
        }

        .data-lengkap .no {
            width: 9mm;
        }

        .data-lengkap .label {
            width: 61mm;
        }

        .data-lengkap .colon {
            width: 7mm;
            text-align: center;
        }

        .data-lengkap .value {
            width: auto;
        }

        .sub {
            padding-left: 8mm !important;
        }

        .daftar-lampiran {
            width: 100%;
            margin-top: 4mm;
        }

        .daftar-lampiran td {
            vertical-align: top;
            padding: 0;
            line-height: 1.22;
        }

        .daftar-lampiran .no {
            width: 9mm;
        }

        .tanda-tangan {
            margin-top: 12mm;
            margin-left: 115mm;
            width: 65mm;
            text-align: left;
        }

        .tanda-tangan .space {
            height: 28mm;
        }

        .tanda-tangan .garis {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    @php

        $data = $pendaftaran->dataPribadi;

        $pendidikan = $pendaftaran->pendidikan;

        $orangTua = $pendaftaran->orangTua;

        $periode = $pendaftaran->periode;

        $jalur = $pendaftaran->jalurBeasiswa;

        $kategori = $pendaftaran->kategoriBeasiswa;

        $tahunAnggaran = $periode?->tahun ?? now()->year;

        $jenisJalur = strtoupper(
            $jalur?->kode
                ?? $jalur?->nama
                ?? ''
        );

        if (str_contains($jenisJalur, 'NON')) {
            $jenisJalurLabel = 'Non Reguler';
        } else {
            $jenisJalurLabel = 'Reguler';
        }

        $namaKategori = $kategori?->nama ?? '-';

        $ipk = $pendidikan?->ipk !== null
            ? number_format((float) $pendidikan->ipk, 2)
            : '-';

        $namaKetuaProdi =
            $pendidikan?->nama_ketua_prodi ?: '-';

        $namaKetuaJurusan =
            $pendidikan?->nama_ketua_jurusan ?: '-';

        $namaDirektur =
            $pendidikan?->nama_direktur ?: '-';

        $namaRektor =
            $pendidikan?->nama_rektor ?: '-';

        $tanggalSurat =
            now()->translatedFormat('d F Y');

    @endphp


    {{-- ============================================================
         HALAMAN 1
         ============================================================ --}}

    <div class="page">

        <div class="page-number">
            14
        </div>

        <div class="title">

            PERMOHONAN BANTUAN BIAYA PENDIDIKAN<br>

            MELALUI PROGRAM KARTU HEBAT MAHASISWA

        </div>


        <div class="subtitle">

            Diajukan sebagai persyaratan permohonan untuk mendapatkan

        </div>

        <div class="subtitle">

            Beasiswa Kartu Hebat Mahasiswa

        </div>

        <div class="subtitle">

            Kabupaten Murung Raya

        </div>

        <div class="subtitle">

            Tahun Anggaran {{ $tahunAnggaran }}

        </div>


        <table class="identitas-awal">

            <tr>

                <td class="label">
                    NAMA MAHASISWA
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $data?->nama_lengkap ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    NIM
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->nim ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    JENJANG PROGRAM
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->jenjang ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    AKREDITASI
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->akreditasi_program_studi ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    JURUSAN
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->jurusan ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    PERGURUAN TINGGI
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->universitas ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    ASAL DESA/KELURAHAN
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $data?->desa ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    ALAMAT PERGURUAN TINGGI
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $pendidikan?->alamat_perguruan_tinggi ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    KATEGORI BEASISWA
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $jenisJalurLabel }}
                </td>

            </tr>


            <tr>

                <td class="label">
                    JENIS BEASISWA
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    {{ $namaKategori }}
                </td>

            </tr>

        </table>


        <div class="penutup-halaman-1">

            Diajukan sebagai persyaratan permohonan untuk mendapatkan<br>

            Beasiswa Kartu Hebat Mahasiswa<br>

            Kabupaten Murung Raya<br>

            Tahun Anggaran {{ $tahunAnggaran }}

        </div>

    </div>



    {{-- ============================================================
         HALAMAN 2
         ============================================================ --}}

    <div class="page">

        <div class="page-number">
            20
        </div>

        <div class="surat-title">
            SURAT PERMOHONAN
        </div>


        <div class="tanggal">

            {{ $data?->tempat_lahir ?: '......' }},
            {{ $tanggalSurat }}

        </div>


        <table class="surat-meta">

            <tr>

                <td class="label">
                    Nomor
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    Lepas
                </td>

                <td style="width: 10mm;"></td>

                <td class="kepada-content">

                    Kepada

                    <br>

                    Yth. Bapak Bupati Murung Raya

                    <br>

                    di -

                    <br>

                    Tempat

                </td>

            </tr>


            <tr>

                <td class="label">
                    Sifat
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    Biasa
                </td>

            </tr>


            <tr>

                <td class="label">
                    Lampiran
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">
                    1 (satu) berkas
                </td>

            </tr>


            <tr>

                <td class="label">
                    Perihal
                </td>

                <td class="colon">
                    :
                </td>

                <td class="value">

                    Mohon Bantuan Biaya Pendidikan
                    untuk Mahasiswa dari
                    Kabupaten Murung Raya

                </td>

            </tr>

        </table>


        <div class="isi-surat">

            <p>
                Dengan Hormat,
            </p>

            <p>
                Yang bertanda tangan di bawah ini :
            </p>


            <table class="data-lengkap">

                <tr>

                    <td class="no">
                        1.
                    </td>

                    <td class="label">
                        Nama Mahasiswa
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $data?->nama_lengkap ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        2.
                    </td>

                    <td class="label">
                        N I M
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->nim ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        3.
                    </td>

                    <td class="label">
                        Jenjang Program
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->jenjang ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        4.
                    </td>

                    <td class="label">
                        Program Studi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->program_studi ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        5.
                    </td>

                    <td class="label">
                        Jurusan
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->jurusan ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        6.
                    </td>

                    <td class="label">
                        Perguruan Tinggi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value"></td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        a. Nama Perguruan Tinggi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->universitas ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        b. Status Perguruan Tinggi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->status_perguruan_tinggi ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        c. Akreditasi Program Studi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->akreditasi_program_studi ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        d. Nama Ketua Prodi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $namaKetuaProdi }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        e. Nama Ketua Jurusan
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $namaKetuaJurusan }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        f. Nama Direktur
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $namaDirektur }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        g. Nama Rektor
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $namaRektor }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        h. Alamat Perguruan Tinggi
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->alamat_perguruan_tinggi ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        i. No.Telp/HP PT
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->no_telp_perguruan_tinggi ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        7.
                    </td>

                    <td class="label">
                        Tahun Mulai Kuliah
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $pendidikan?->tahun_masuk ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        8.
                    </td>

                    <td class="label">
                        Alamat Mahasiswa
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $data?->alamat ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        9.
                    </td>

                    <td class="label">
                        Telp/HP Mahasiswa
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $data?->no_hp ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        10.
                    </td>

                    <td class="label">
                        Nama
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value"></td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        a. Ayah
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $orangTua?->nama_ayah ?: '-' }}
                    </td>

                </tr>


                <tr>

                    <td></td>

                    <td class="label sub">
                        b. Ibu
                    </td>

                    <td class="colon">
                        :
                    </td>

                    <td class="value">
                        {{ $orangTua?->nama_ibu ?: '-' }}
                    </td>

                </tr>

            </table>


            <p>

                Dengan ini mengajukan permohonan bantuan biaya pendidikan
                untuk keperluan perkuliahan.

            </p>


            <p>

                Sebagai bahan pertimbangan bagi Bapak Bupati, bersama ini
                saya lampirkan berkas administrasi sebagai berikut :

            </p>


            <table class="daftar-lampiran">

                <tr>

                    <td class="no">
                        1.
                    </td>

                    <td>
                        Surat keterangan tidak mampu dari Kepala Desa/Lurah
                        yang diketahui oleh Camat;
                    </td>

                </tr>

            </table>

        </div>

    </div>



    {{-- ============================================================
         HALAMAN 3
         ============================================================ --}}

    <div class="page">

        <div class="page-number">
            21
        </div>


        <div class="isi-surat">

            <table class="daftar-lampiran">

                <tr>

                    <td class="no">
                        2.
                    </td>

                    <td>
                        fotokopi ijazah kelulusan SMA sederajat dan fotocopy
                        rapot kelas XII atau Surat Keterangan Kelulusan dari
                        Kepala Sekolah bagi calon penerima bantuan yang baru
                        lulus SMA/sederajat;
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        3.
                    </td>

                    <td>
                        fotokopi Kartu Tanda Penduduk (KTP);
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        4.
                    </td>

                    <td>
                        fotokopi Kartu Keluarga (KK);
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        5.
                    </td>

                    <td>
                        surat keterangan lulus/diterima pada Perguruan Tinggi
                        tempat mendaftar;
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        6.
                    </td>

                    <td>
                        fotokopi sertifikat akreditasi Perguruan Tinggi;
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        7.
                    </td>

                    <td>
                        fotokopi Surat Keputusan tentang Penunjukan
                        Kepengurusan Organisasi di Perguruan Tinggi;
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        8.
                    </td>

                    <td>
                        fotokopi Buku Rekening Tabungan PT. Bank Pembangunan
                        Daerah Kalimantan Tengah;
                    </td>

                </tr>


                <tr>

                    <td class="no">
                        9.
                    </td>

                    <td>
                        pakta integritas yang dibubuhi materai Rp. 10.000,-
                        (Sepuluh Ribu Rupiah).
                    </td>

                </tr>

            </table>


            <p style="margin-top: 10mm;">

                Demikian Surat Permohonan ini diajukan, atas perhatian dan
                bantuan Bapak Bupati diucapkan terima kasih.

            </p>


            <div class="tanda-tangan">

                <div>
                    Pemohon,
                </div>

                <div class="space"></div>

                <div class="garis">
                    ........................................
                </div>

                <div>
                    NIM {{ $pendidikan?->nim ?: '........................' }}
                </div>

            </div>

        </div>

    </div>

</body>

</html>