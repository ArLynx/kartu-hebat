<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: F4;
            margin: 18mm 20mm 15mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
        }

        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 9mm;
        }

        .intro {
            margin-bottom: 4mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* =========================================================
           IDENTITAS
           ========================================================= */

        .identity-table td {
            vertical-align: top;
            padding: 0 0 1.2mm 0;
        }

        .identity-letter {
            width: 7mm;
        }

        .identity-label {
            width: 50mm;
        }

        .identity-colon {
            width: 5mm;
            text-align: left;
        }

        .identity-value {
            width: auto;
        }

        /* =========================================================
           PERNYATAAN
           ========================================================= */

        .statement-intro {
            margin-top: 4mm;
            margin-bottom: 3mm;
            text-align: justify;
        }

        .statement-table {
            width: 100%;
        }

        .statement-table td {
            vertical-align: top;
            padding-bottom: 2mm;
        }

        .statement-letter {
            width: 7mm;
        }

        .statement-content {
            text-align: justify;
        }

        .closing {
            margin-top: 2mm;
            text-align: justify;
        }

        /* =========================================================
           TANDA TANGAN
           ========================================================= */

        .signature-section {
            margin-top: 5mm;
        }

        .signature-date {
            text-align: right;
            margin-bottom: 3mm;
        }

        .signature-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }

        .signature-left {
            padding-right: 10mm !important;
        }

        .signature-right {
            padding-left: 10mm !important;
        }

        .signature-title {
            min-height: 8mm;
            line-height: 1.25;
        }

        /*
        | Ruang kosong untuk tanda tangan manual.
        | Materai ditempel sendiri pada dokumen setelah dicetak.
        */
        .signature-space {
            height: 22mm;
        }

        .signature-name {
            text-align: center;
            white-space: nowrap;
        }

        .signature-nim {
            margin-top: 1mm;
            text-align: center;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @php
        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $dataPribadi = $pendaftaran->dataPribadi;
        $pendidikan = $pendaftaran->pendidikan;
        $orangTua = $pendaftaran->orangTua;

        /*
        |--------------------------------------------------------------------------
        | Tempat & tanggal lahir
        |--------------------------------------------------------------------------
        */

        $tempatTanggalLahir = '-';

        if ($dataPribadi) {
            $tempat = trim((string) $dataPribadi->tempat_lahir);

            $tanggal = $dataPribadi->tanggal_lahir
                ? $dataPribadi->tanggal_lahir->translatedFormat('d F Y')
                : null;

            if ($tempat && $tanggal) {
                $tempatTanggalLahir = $tempat . ', ' . $tanggal;
            } elseif ($tempat) {
                $tempatTanggalLahir = $tempat;
            } elseif ($tanggal) {
                $tempatTanggalLahir = $tanggal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Alamat sesuai KTP
        |--------------------------------------------------------------------------
        */

        $alamatKtp = collect([
            $dataPribadi?->alamat
                ? $dataPribadi->alamat
                : null,

            $dataPribadi?->desa
                ? 'Desa ' . $dataPribadi->desa
                : null,

            $dataPribadi?->kecamatan
                ? 'Kecamatan ' . $dataPribadi->kecamatan
                : null,

            $dataPribadi?->kabupaten
                ? 'Kabupaten ' . $dataPribadi->kabupaten
                : null,

            $dataPribadi?->provinsi
                ? 'Provinsi ' . $dataPribadi->provinsi
                : null,

            $dataPribadi?->kode_pos
                ? 'Kode Pos ' . $dataPribadi->kode_pos
                : null,
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(', ');

        if ($alamatKtp === '') {
            $alamatKtp = '-';
        }

        /*
        |--------------------------------------------------------------------------
        | Data pendidikan
        |--------------------------------------------------------------------------
        */

        $namaMahasiswa = $dataPribadi?->nama_lengkap ?? '-';

        $namaPerguruanTinggi = $pendidikan?->universitas ?? '-';

        $jurusan = $pendidikan?->jurusan ?? '-';

        $programStudi = $pendidikan?->program_studi ?? '-';

        $nim = $pendidikan?->nim ?? '-';

        $semesterTingkat = trim(
            ($pendidikan?->jenjang ?? '') .
            (($pendidikan?->jenjang && $pendidikan?->semester !== null) ? ' / ' : '') .
            ($pendidikan?->semester ?? '')
        );

        if ($semesterTingkat === '') {
            $semesterTingkat = '-';
        }

        $tahunMasuk = $pendidikan?->tahun_masuk ?? '-';

        /*
        |--------------------------------------------------------------------------
        | Nama penandatangan orang tua / wali
        |--------------------------------------------------------------------------
        |
        | Sudah ditentukan oleh Controller berdasarkan:
        | - memiliki_wali
        | - status_ayah
        | - status_ibu
        |
        */

        $namaPenandatanganOrangTua =
            $namaPenandatanganOrangTua
            ?? $orangTua?->nama_ayah
            ?? '-';

        /*
        |--------------------------------------------------------------------------
        | Tempat tanggal tanda tangan
        |--------------------------------------------------------------------------
        |
        | Untuk sementara menggunakan kabupaten/kota dari data mahasiswa.
        */

        $tempatTtd = 'Puruk Cahu';

        $tanggalTtd = now()->translatedFormat('d F Y');
    @endphp


    {{-- =========================================================
         JUDUL
         ========================================================= --}}

    <div class="title">
        PAKTA INTEGRITAS
    </div>


    {{-- =========================================================
         PEMBUKA
         ========================================================= --}}

    <div class="intro">
        Saya yang bertandatangan di bawah ini:
    </div>


    {{-- =========================================================
         IDENTITAS MAHASISWA
         ========================================================= --}}

    <table class="identity-table">

        <tr>
            <td class="identity-letter">a.</td>
            <td class="identity-label">Nama</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $namaMahasiswa }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">b.</td>
            <td class="identity-label">Tempat &amp; Tanggal Lahir</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $tempatTanggalLahir }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">c.</td>
            <td class="identity-label">Alamat (Sesuai KTP)</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $alamatKtp }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">d.</td>
            <td class="identity-label">Nama Perguruan Tinggi</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $namaPerguruanTinggi }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">e.</td>
            <td class="identity-label">Jurusan</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $jurusan }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">f.</td>
            <td class="identity-label">Program Studi</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $programStudi }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">g.</td>
            <td class="identity-label">NIM</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $nim }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">h.</td>
            <td class="identity-label">Semester/Tingkat</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $semesterTingkat }}
            </td>
        </tr>

        <tr>
            <td class="identity-letter">i.</td>
            <td class="identity-label">Tahun Masuk</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $tahunMasuk }}
            </td>
        </tr>

    </table>


    {{-- =========================================================
         PENGANTAR PERNYATAAN
         ========================================================= --}}

    <div class="statement-intro">
        Dalam rangka penerimaan Bantuan Biaya Pendidikan melalui Program
        Kartu Hebat Mahasiswa, dengan ini menyatakan bahwa:
    </div>


    {{-- =========================================================
         ISI PAKTA
         ========================================================= --}}

    <table class="statement-table">

        <tr>
            <td class="statement-letter">a.</td>
            <td class="statement-content">
                Saya tidak pernah melakukan pemalsuan dokumen yang menjadi
                persyaratan administrasi dalam mengajukan permohonan untuk
                mendapatkan Bantuan Biaya Pendidikan;
            </td>
        </tr>

        <tr>
            <td class="statement-letter">b.</td>
            <td class="statement-content">
                Saya akan menggunakan dana Bantuan Biaya pendidikan ini untuk
                ...........; (diisi sesuai dengan ketentuan Pasal 8 Peraturan
                Bupati Nomor ... Tahun ..... tentang Kartu Hebat Mahasiswa)
            </td>
        </tr>

        <tr>
            <td class="statement-letter">c.</td>
            <td class="statement-content">
                Saya akan mempertanggungjawabkan semua dana Bantuan Biaya
                Pendidikan ini sesuai dengan peruntukannya sesuai dengan huruf b
                tersebut di atas dan menyampaikan laporan pertanggungjawabannya
                tepat waktu;
            </td>
        </tr>

        <tr>
            <td class="statement-letter">d.</td>
            <td class="statement-content">
                Saya tidak sedang menerima beasiswa sejenis yang bersumber dari
                APBN, APBD, atau pihak lain;
            </td>
        </tr>

        <tr>
            <td class="statement-letter">e.</td>
            <td class="statement-content">
                Saya bersedia mematuhi seluruh ketentuan Program Kartu Hebat
                Mahasiswa; dan
            </td>
        </tr>

        <tr>
            <td class="statement-letter">f.</td>
            <td class="statement-content">
                Apabila saya melanggar Pakta Integritas ini, saya bersedia
                mengembalikan secara keseluruhan dana Bantuan Biaya pendidikan
                yang saya terima ke Kas Daerah dan akan dikenakan sanksi sesuai
                dengan ketentuan peraturan perundang-undangan yang berlaku.
            </td>
        </tr>

    </table>


    {{-- =========================================================
         PENUTUP
         ========================================================= --}}

    <div class="closing">
        Demikian Pakta Integritas ini dibuat dalam keadaan sadar, sehat jasmani
        dan rohani tidak ada paksaan dari pihak manapun serta ditandatangani
        di atas materai cukup, sehingga memiliki kekuatan hukum yang mengikat.
    </div>


    {{-- =========================================================
         TANDA TANGAN
         ========================================================= --}}

    <div class="signature-section">

        <div class="signature-date">
            {{ $tempatTtd }}, {{ $tanggalTtd }}
        </div>

        <table class="signature-table">

            <tr>

                {{-- ORANG TUA / WALI / KELUARGA --}}
                <td class="signature-left">

                    <div class="signature-title">
                        Mengetahui<br>
                        Orang Tua/Wali/Keluarga
                    </div>

                    <div class="signature-space"></div>

                    <div class="signature-name">
                        {{ $namaPenandatanganOrangTua }}
                    </div>

                </td>


                {{-- MAHASISWA --}}
                <td class="signature-right">

                    <div class="signature-title">
                        Yang membuat pernyataan,
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