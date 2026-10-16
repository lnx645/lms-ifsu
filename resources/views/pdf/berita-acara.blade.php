<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Ujian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10pt; color: #222; padding: 28pt; line-height: 1.5; }
        .kop { text-align: center; border-bottom: 3px double #222; padding-bottom: 10pt; margin-bottom: 16pt; }
        .kop h2 { font-size: 13pt; }
        .kop p { font-size: 9pt; }
        h1 { font-size: 13pt; text-align: center; text-decoration: underline; margin-bottom: 4pt; }
        .nomor { text-align: center; font-size: 9pt; margin-bottom: 14pt; }
        table.info { width: 100%; margin-bottom: 12pt; }
        table.info td { padding: 2pt 0; vertical-align: top; }
        table.info td.label { width: 28%; }
        table.rekap { width: 100%; border-collapse: collapse; margin: 10pt 0; }
        table.rekap th, table.rekap td { border: 1px solid #444; padding: 5pt 8pt; text-align: left; }
        table.rekap th { background: #eee; }
        .catatan { border: 1px solid #999; min-height: 60pt; padding: 6pt; margin: 8pt 0 16pt; }
        .ttd { display: flex; justify-content: space-between; text-align: center; margin-top: 24pt; }
        .ttd div { width: 40%; }
        .ttd .nama { margin-top: 48pt; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>SMK INFORMATIKA SUMEDANG</h2>
        <p>Berita Acara Pelaksanaan Ujian Berbasis Komputer</p>
    </div>

    <h1>BERITA ACARA PELAKSANAAN UJIAN</h1>
    @if ($beritaAcara->nomor_surat)
        <p class="nomor"><strong>Nomor: {{ $beritaAcara->nomor_surat }}</strong></p>
    @endif
    <p class="nomor">Pada hari ini {{ $beritaAcara->tanggal->translatedFormat('l, d F Y') }} telah dilaksanakan ujian sebagai berikut:</p>

    <table class="info">
        <tr><td class="label">Ujian</td><td>: {{ $ujian->judul }} ({{ strtoupper($ujian->kategori) }})</td></tr>
        <tr><td class="label">Sesi</td><td>: {{ $beritaAcara->sesi?->nama ?? 'Tanpa sesi' }}</td></tr>
        @if ($beritaAcara->sesi)
            <tr><td class="label">Waktu</td><td>: {{ $beritaAcara->sesi->waktu_mulai->translatedFormat('H:i') }} – {{ $beritaAcara->sesi->waktu_selesai->translatedFormat('H:i') }}</td></tr>
            <tr><td class="label">Ruang</td><td>: {{ $beritaAcara->sesi->ruang?->nama ?? '-' }}</td></tr>
        @endif
        <tr><td class="label">Pengawas</td><td>: {{ $beritaAcara->nama_pengawas ?? $beritaAcara->sesi?->pengawas->pluck('nama_pengawas')->implode(', ') ?? '-' }}</td></tr>
    </table>

    <table class="rekap">
        <tr><th>Uraian</th><th style="width:30%">Jumlah</th></tr>
        <tr><td>Peserta terdaftar</td><td>{{ $beritaAcara->jumlah_peserta }} orang</td></tr>
        <tr><td>Hadir</td><td>{{ $beritaAcara->jumlah_hadir }} orang</td></tr>
        <tr><td>Tidak hadir</td><td>{{ $beritaAcara->jumlah_tidak_hadir }} orang</td></tr>
        <tr><td>Pelanggaran tercatat</td><td>{{ $beritaAcara->jumlah_pelanggaran }} kejadian</td></tr>
    </table>

    <p><strong>Catatan pengawas:</strong></p>
    <div class="catatan">{{ $beritaAcara->catatan ?? '—' }}</div>

    <p>Demikian berita acara ini dibuat dengan sebenarnya.</p>

    <div class="ttd">
        <div>Pengawas<div class="nama">{{ $beritaAcara->nama_pengawas ?? '( ........................ )' }}</div></div>
        <div>Kepala Sekolah<div class="nama">{{ $beritaAcara->nama_kepala_sekolah ?? '( ........................ )' }}</div></div>
    </div>
</body>
</html>
