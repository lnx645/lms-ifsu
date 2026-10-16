<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Hadir — {{ $sesi->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 9pt; color: #222; padding: 20pt; }
        h1 { font-size: 13pt; text-align: center; margin-bottom: 2pt; }
        .meta { text-align: center; font-size: 8.5pt; color: #555; margin-bottom: 12pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #888; padding: 4pt 6pt; text-align: left; }
        th { background: #ddebf7; font-size: 8.5pt; }
        td { font-size: 8.5pt; height: 18pt; }
        td.no, td.hadir { text-align: center; }
        .hadir-ya { font-weight: bold; color: #1a7a2e; }
    </style>
</head>
<body>
    <h1>DAFTAR HADIR PESERTA UJIAN</h1>
    <p class="meta">
        {{ $ujian->judul }} &mdash; {{ $sesi->nama }}<br>
        {{ $sesi->waktu_mulai->translatedFormat('d M Y H:i') }} – {{ $sesi->waktu_selesai->translatedFormat('H:i') }}
        @if ($sesi->ruang) &mdash; Ruang {{ $sesi->ruang->nama }} @endif
    </p>
    <table>
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:16%">NISN</th>
                <th>Nama Peserta</th>
                <th style="width:10%">Status</th>
                <th style="width:22%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pesertas as $i => $siswa)
                @php $hadir = in_array($siswa->nisn, $hadirNisns, true); @endphp
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $siswa->nisn }}</td>
                    <td>{{ $siswa->nama_lengkap }}</td>
                    <td class="hadir">{!! $hadir ? '<span class="hadir-ya">Hadir</span>' : '-' !!}</td>
                    <td>{{ $i + 1 }}. ....................</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 12pt; font-size: 8pt; color: #666;">Status "Hadir" terisi otomatis dari data pengerjaan sistem. Jumlah peserta: {{ count($pesertas) }} orang.</p>
</body>
</html>
