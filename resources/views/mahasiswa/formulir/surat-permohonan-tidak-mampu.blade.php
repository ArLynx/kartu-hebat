<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Permohonan Beasiswa Tidak Mampu - {{ $pendaftaran->nomor_pendaftaran }}</title>

    <style>
        @page {
            size: 215.9mm 330.2mm; /* F4 */
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
            font-size: 12pt;
            line-height: 1.25;
            color: #000000;
        }

        .page-break {
            page-break-after: always;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 5mm;
        }

        /* HEADER SURAT */
        .letter-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3.5mm;
        }

        .letter-header td {
            vertical-align: top;
            font-size: 12pt;
            line-height: 1.25;
            padding: 0;
        }

        .header-left {
            width: 54%;
        }

        .header-right {
            width: 46%;
            padding-left: 6mm;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 0.4mm 0;
            font-size: 12pt;
            line-height: 1.25;
        }

        .meta-label {
            width: 20mm;
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

        /* TABEL IDENTITAS */
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2.5mm;
            page-break-inside: auto;
        }

        .identity-table td {
            vertical-align: top;
            padding: 0.4mm 0;
            font-size: 12pt;
            line-height: 1.2;
        }

        .id-no {
            width: 6mm;
            text-align: left;
        }

        .id-label {
            width: 56mm;
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
            line-height: 1.25;
        }

        .attachment-intro {
            text-align: justify;
            margin: 0 0 1.5mm 0;
            font-size: 12pt;
            line-height: 1.25;
        }

        /* TABEL LAMPIRAN */
        .attachment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attachment-table td {
            vertical-align: top;
            padding: 0.4mm 0;
            font-size: 12pt;
            line-height: 1.25;
        }

        .att-no {
            width: 6mm;
            text-align: left;
        }

        .att-text {
            width: auto;
            text-align: justify;
        }

        .closing {
            margin-top: 3mm;
            text-align: justify;
            font-size: 12pt;
            line-height: 1.25;
        }

        /* TANDA TANGAN */
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

    $namaMahasiswa = $dataPribadi?->nama_lengkap ?? '-';
    $nim = $pendaftaran->user?->nim ?? ($pendidikan?->nim ?? '-');
    $jenjang = $pendidikan?->jenjang ?? '-';

    $akreditasi = $pendidikan?->akreditasi_perguruan_tinggi ?? '-';
    $jurusan = $pendidikan?->jurusan ?? '-';
    $programStudi = $pendidikan?->program_studi ?? '-';
    $perguruanTinggi = $pendidikan?->universitas ?? '-';
    $alamatPerguruanTinggi = $pendidikan?->alamat_perguruan_tinggi ?? '-';

    $ipk = $pendidikan?->ipk !== null
        ? number_format((float) $pendidikan->ipk, 2)
        : '-';

    $tahunMulaiKuliah = $pendidikan?->tahun_masuk ?? '-';

    $jenisWilayah = 'Desa';
    if ($dataPribadi?->village_id) {
        $village = \App\Models\Village::find($dataPribadi->village_id);
        if ($village) {
            $jenisWilayah = $village->type === 'kelurahan' ? 'Kelurahan' : 'Desa';
        }
    }

    $alamatMahasiswa = collect([
        $dataPribadi?->alamat ?: null,
        $dataPribadi?->desa ? $jenisWilayah . ' ' . $dataPribadi->desa : null,
        $dataPribadi?->kecamatan ? 'Kecamatan ' . $dataPribadi->kecamatan : null,
        $dataPribadi?->kabupaten ? 'Kabupaten ' . $dataPribadi->kabupaten : null,
        $dataPribadi?->provinsi ? 'Provinsi ' . $dataPribadi->provinsi : null,
        $dataPribadi?->kode_pos ? 'Kode Pos ' . $dataPribadi->kode_pos : null,
    ])
        ->filter(fn ($value) => filled($value))
        ->implode(', ');

    if ($alamatMahasiswa === '') {
        $alamatMahasiswa = '-';
    }

    $telpMahasiswa = $dataPribadi?->no_hp ?? '-';

    $namaAyah = $orangTua?->nama_ayah ?? '-';
    $namaIbu = $orangTua?->nama_ibu ?? '-';

    $tempatSurat = 'Puruk Cahu';
    $tanggalSurat = now()->translatedFormat('d F Y');
@endphp

<body>

    <div class="title">
        SURAT PERMOHONAN
    </div>

    <table class="letter-header">
        <tr>
            {{-- KIRI: NOMOR, SIFAT, LAMPIRAN, PERIHAL --}}
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

            {{-- KANAN: TEMPAT TANGGAL & TUJUAN --}}
            <td class="header-right">
                <div style="margin-bottom: 2mm;">
                    {{ $tempatSurat }}, {{ $tanggalSurat }}
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
            <td class="id-label">Indeks Prestasi Total</td>
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
            <td class="id-label" style="padding-left: 3mm;">b. Akreditasi Perguruan Tinggi</td>
            <td class="id-colon">:</td>
            <td class="id-val">{{ $akreditasi }}</td>
        </tr>
        <tr>
            <td class="id-no"></td>
            <td class="id-label" style="padding-left: 3mm;">c. Alamat Perguruan Tinggi</td>
            <td class="id-colon">:</td>
            <td class="id-val">{{ $alamatPerguruanTinggi }}</td>
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
            <td class="att-text">Scan asli Kartu Tanda Penduduk (KTP);</td>
        </tr>
        <tr>
            <td class="att-no">2.</td>
            <td class="att-text">Scan asli Kartu Keluarga (KK);</td>
        </tr>
        <tr>
            <td class="att-no">3.</td>
            <td class="att-text">Scan asli Kartu Mahasiswa yang masih berlaku;</td>
        </tr>
        <tr>
            <td class="att-no">4.</td>
            <td class="att-text">Scan asli Surat Keterangan Aktif Kuliah dari Perguruan Tinggi;</td>
        </tr>
        <tr>
            <td class="att-no">5.</td>
            <td class="att-text">Scan asli Nilai Indek Prestasi Total Atau Kumulatif;</td>
        </tr>
        <tr>
            <td class="att-no">6.</td>
            <td class="att-text">Scan Atau Tangkapan Layar Terdaftar Aktif Sebagai Mahasiswa Pada PDDIKTI;</td>
        </tr>
        <tr>
            <td class="att-no">7.</td>
            <td class="att-text">Scan asli sertifikat akreditas Perguruan Tinggi;</td>
        </tr>
    </table>

    <div class="page-break"></div>

    {{-- ================================================================
         HALAMAN 2 - LAMPIRAN 8-9, PENUTUP & TANDA TANGAN
         ================================================================= --}}
    <div>
        <table class="attachment-table">
            <tr>
                <td class="att-no">8.</td>
                <td class="att-text">Scan asli Buku Rekening Tabungan PT. Bank Pembangunan Daerah Kalimantan Tengah; dan</td>
            </tr>
            <tr>
                <td class="att-no">9.</td>
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
                    <div>Pemohon,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $namaMahasiswa }}</div>
                    <div class="signature-nim">NIM {{ $nim }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
