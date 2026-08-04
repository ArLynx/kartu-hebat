<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #172033; }
        h1 { margin: 0; font-size: 18px; }
        .meta { margin: 6px 0 18px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0b1f3a; color: white; padding: 7px 5px; text-align: left; }
        td { border-bottom: 1px solid #dbe3ef; padding: 6px 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $reportTitle }}</h1>
    <div class="meta">Periode {{ config('kartu_hebat.current_period') }} · Dibuat {{ $generatedAt->format('d-m-Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Jalur</th>
                <th>Rank</th>
                <th>Nomor</th>
                <th>Mahasiswa</th>
                <th>NIM</th>
                <th>Perguruan Tinggi</th>
                <th>IPK/Smt</th>
                <th>Desil S/P</th>
                <th>Wilayah</th>
                <th>Skor</th>
                <th>Keputusan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->application_type?->label() }}</td>
                    <td>{{ $application->selection?->rank }}</td>
                    <td>{{ $application->nomor_pengajuan }}</td>
                    <td>{{ $application->mahasiswa->name }}</td>
                    <td>{{ $application->mahasiswa->profile?->nim }}</td>
                    <td>{{ $application->mahasiswa->profile?->universitas }}</td>
                    <td>{{ $application->mahasiswa->profile?->ipk ?? '-' }} / {{ $application->mahasiswa->profile?->semester ?? '-' }}</td>
                    <td>{{ $application->mahasiswa->profile?->desil_sosial ?? '-' }} / {{ $application->mahasiswa->profile?->desil_pendidikan ?? '-' }}</td>
                    <td>{{ $application->mahasiswa->profile?->village?->display_name }}, {{ $application->mahasiswa->profile?->village?->kecamatan?->name }}</td>
                    <td>{{ number_format((float) ($application->selection?->final_score ?? 0), 2) }}</td>
                    <td>{{ $application->selection?->published_at ? $application->status->label() : ($application->selection?->manual_decision ?? 'Menunggu') }}</td>
                </tr>
            @empty
                <tr><td colspan="11">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
