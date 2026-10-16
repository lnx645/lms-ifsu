<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Ujian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #1a1a1a; padding: 20pt; background: #fff; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 4pt; color: #1a3a5c; }
        .sub { text-align: center; font-size: 9pt; color: #666; margin-bottom: 16pt; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a3a5c; color: #fff; padding: 8pt 6pt; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.5pt; }
        td { padding: 7pt 6pt; border-bottom: 0.5pt solid #ddd; font-size: 8.5pt; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2pt 8pt; border-radius: 10pt; font-size: 7.5pt; font-weight: 600; }
        .badge-terbit { background: #d1fae5; color: #065f46; }
        .badge-diajukan { background: #fef3c7; color: #92400e; }
        .sesi { margin-top: 4pt; padding-top: 4pt; border-top: 0.5pt dotted #ccc; font-size: 8pt; color: #555; }
        .sesi:first-child { margin-top: 0; padding-top: 0; border-top: none; }
    </style>
</head>
<body>
    <h1>JADWAL UJIAN</h1>
    <p class="sub">SMK Informatika Sumedang · Dicetak {{ $tanggal_cetak }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 22%;">Ujian</th>
                <th style="width: 12%;">Kelas</th>
                <th style="width: 15%;">Mata Pelajaran</th>
                <th style="width: 30%;">Sesi & Waktu</th>
                <th style="width: 10%;">Ruang</th>
                <th style="width: 7%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ujians as $i => $ujian)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $ujian->judul }}</strong><br>
                        <span style="color: #666; font-size: 7.5pt;">{{ strtoupper($ujian->kategori) }} · {{ $ujian->durasi_menit }} menit</span>
                    </td>
                    <td>{{ $ujian->guruKelas?->kelas?->nama ?? '-' }}</td>
                    <td>{{ $ujian->guruKelas?->matpel?->name ?? '-' }}</td>
                    <td>
                        @forelse($ujian->sesis as $sesi)
                            <div class="sesi">
                                <strong>{{ $sesi->nama }}:</strong>
                                {{ $sesi->waktu_mulai?->translatedFormat('d M Y H:i') ?? '-' }}
                                &ndash; {{ $sesi->waktu_selesai?->translatedFormat('H:i') ?? '-' }}
                            </div>
                        @empty
                            <span style="color: #999;">Belum ada sesi</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse($ujian->sesis as $sesi)
                            <div class="sesi">{{ $sesi->ruang?->nama ?? '-' }}</div>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td>
                        <span class="badge badge-{{ $ujian->status }}">{{ ucfirst($ujian->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; color: #999;">Belum ada ujian terjadwal.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
