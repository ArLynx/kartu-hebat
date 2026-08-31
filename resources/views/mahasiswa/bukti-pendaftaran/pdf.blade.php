<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Bukti Pendaftaran {{ $pendaftaran->nomor_pendaftaran }}
    </title>


    <style>

        @page {
            size: A4;
            margin: 18px;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: DejaVu Sans, sans-serif;

            color: #172033;

            font-size: 11px;

        }


        .card {

            border: 1px solid #d8dee8;

            border-radius: 12px;

            overflow: hidden;

        }


        /* ================================================== */
        /* KOP */
        /* ================================================== */

        .header {

            padding: 18px 20px;

            border-bottom: 4px solid #0f5ca8;

        }


        .logo {

            width: 65px;

            height: 65px;

            object-fit: contain;

            vertical-align: middle;

        }


        .header-text {

            display: inline-block;

            vertical-align: middle;

            margin-left: 14px;

        }


        .government {

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;

            color: #475569;

        }


        .program {

            margin-top: 4px;

            font-size: 18px;

            font-weight: bold;

            text-transform: uppercase;

            color: #071b3a;

        }


        .subtitle {

            margin-top: 4px;

            font-size: 10px;

            color: #64748b;

        }


        /* ================================================== */
        /* SECTION */
        /* ================================================== */

        .section {

            padding: 15px 20px;

        }


        .section-gray {

            padding: 15px 20px;

            background: #f8fafc;

            border-top: 1px solid #e2e8f0;

        }


        .title {

            font-size: 9px;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: 1px;

            color: #0f5ca8;

        }


        .number {

            margin-top: 6px;

            font-size: 20px;

            font-weight: bold;

            color: #071b3a;

        }


        .period {

            margin-top: 5px;

            color: #64748b;

        }


        /* ================================================== */
        /* TABLE */
        /* ================================================== */

        table {

            width: 100%;

            border-collapse: collapse;

        }


        td {

            padding: 7px 4px;

            vertical-align: top;

        }


        .label {

            width: 27%;

            color: #64748b;

            font-size: 9px;

        }


        .value {

            font-weight: bold;

            color: #172033;

        }


        /* ================================================== */
        /* BARCODE */
        /* ================================================== */

        .barcode-section {

            border-top: 1px solid #e2e8f0;

            padding: 14px 20px;

            text-align: center;
        }


        .barcode-title {

            font-size: 9px;

            font-weight: bold;

            color: #64748b;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 8px;

        }


        .barcode {

            width: 260px;

            height: 65px;

        }


        .barcode-number {

            margin-top: 5px;

            font-size: 9px;

            color: #475569;

            letter-spacing: 1px;

        }


        /* ================================================== */
        /* FOOTER */
        /* ================================================== */

        .footer {

            border-top: 1px solid #e2e8f0;

            padding: 10px 20px;

            color: #64748b;

            font-size: 9px;

            line-height: 1.5;
        }


        .signature {

            margin-top: 6px;

            text-align: right;

        }

    </style>

</head>


<body>


<div class="card">


    {{-- ================================================== --}}
    {{-- KOP --}}
    {{-- ================================================== --}}

    <div class="header">


        @if($logo)

            <img
                src="{{ $logo }}"
                class="logo"
                alt="Logo"
            >

        @endif


        <div class="header-text">

            <div class="government">

                Pemerintah Kabupaten Murung Raya

            </div>


            <div class="program">

                Kartu Hebat Mahasiswa

            </div>


            <div class="subtitle">

                Kartu Bukti Pendaftaran

            </div>

        </div>

    </div>



    {{-- ================================================== --}}
    {{-- NOMOR --}}
    {{-- ================================================== --}}

    <div class="section">


        <div class="title">

            Bukti Pendaftaran

        </div>


        <div class="number">

            {{ $pendaftaran->nomor_pendaftaran }}

        </div>


        <div class="period">

            {{ $pendaftaran->periode?->nama ?? '-' }}

        </div>

    </div>



    {{-- ================================================== --}}
    {{-- DATA PENDAFTAR --}}
    {{-- ================================================== --}}

    <div class="section">


        <div class="title">

            Data Pendaftar

        </div>


        <table style="margin-top: 10px;">


            <tr>

                <td class="label">
                    Nama Lengkap
                </td>

                <td class="value">

                    {{
                        $pendaftaran->dataPribadi?->nama_lengkap
                        ?? $pendaftaran->user?->name
                        ?? '-'
                    }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    NIK
                </td>

                <td class="value">

                    {{ $pendaftaran->dataPribadi?->nik ?? '-' }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    NIM
                </td>

                <td class="value">

                    {{ $pendaftaran->pendidikan?->nim ?? '-' }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    Perguruan Tinggi
                </td>

                <td class="value">

                    {{ $pendaftaran->pendidikan?->universitas ?? '-' }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    Program Studi
                </td>

                <td class="value">

                    {{ $pendaftaran->pendidikan?->program_studi ?? '-' }}

                </td>

            </tr>

            <tr>
                <td class="label">
                    Kategori Beasiswa
                </td>

                <td class="value">
                    {{ $pendaftaran->jalurBeasiswa?->nama ?? '-' }}
                </td>
            </tr>

            <tr>

                <td class="label">
                    Jenis Beasiswa
                </td>

                <td class="value">

                    {{ $pendaftaran->kategoriBeasiswa?->nama ?? '-' }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    Periode
                </td>

                <td class="value">

                    {{ $pendaftaran->periode?->nama ?? '-' }}

                </td>

            </tr>


            <tr>

                <td class="label">
                    Tanggal Pendaftaran
                </td>

                <td class="value">

                    {{
                        (
                            $pendaftaran->application?->submitted_at
                            ?? $pendaftaran->submitted_at
                        )?->format('d F Y H:i')
                        ?? '-'
                    }}

                </td>

            </tr>


        </table>

    </div>



    {{-- ================================================== --}}
    {{-- ALAMAT LENGKAP --}}
    {{-- ================================================== --}}

    <div class="section-gray">


        <div class="title">

            Alamat Pendaftar

        </div>


        <table style="margin-top: 10px;">


            {{-- JALAN --}}

            <tr>

                <td class="label">

                    Alamat / Jalan

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->alamat ?? '-' }}

                </td>

            </tr>


            {{-- PROVINSI --}}

            <tr>

                <td class="label">

                    Provinsi

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->provinsi ?? '-' }}

                </td>

            </tr>


            {{-- KABUPATEN --}}

            <tr>

                <td class="label">

                    Kabupaten

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->kabupaten ?? '-' }}

                </td>

            </tr>


            {{-- KECAMATAN --}}

            <tr>

                <td class="label">

                    Kecamatan

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->kecamatan ?? '-' }}

                </td>

            </tr>


            {{-- DESA --}}

            <tr>

                <td class="label">

                    Desa / Kelurahan

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->desa ?? '-' }}

                </td>

            </tr>


            {{-- KODE POS --}}

            <tr>

                <td class="label">

                    Kode Pos

                </td>


                <td class="value">

                    {{ $pendaftaran->dataPribadi?->kode_pos ?? '-' }}

                </td>

            </tr>


        </table>

    </div>

    {{-- ================================================== --}}
    {{-- QR CODE --}}
    {{-- ================================================== --}}

    <div class="barcode-section">

        <div class="barcode-title">
            Scan QR Code untuk Mengunjungi Website
        </div>

       @if($barcode)

            <img
                src="{{ $barcode }}"
                alt="QR Code Website Kartu Hebat Mahasiswa"
                style="width: 150px; height: 150px;"
            >

        @endif

        <div class="barcode-number">
            Kartu Hebat Mahasiswa
        </div>

    </div>



    {{-- ================================================== --}}
    {{-- FOOTER --}}
    {{-- ================================================== --}}

    <div class="footer">


        Kartu ini merupakan bukti bahwa mahasiswa telah
        melakukan pendaftaran Kartu Hebat Mahasiswa
        melalui sistem resmi Pemerintah Kabupaten Murung Raya.


        <div class="signature">

            Puruk Cahu,
            {{
                (
                    $pendaftaran->application?->submitted_at
                    ?? $pendaftaran->submitted_at
                    ?? now()
                )->format('d F Y')
            }}

        </div>


    </div>


</div>


</body>

</html>