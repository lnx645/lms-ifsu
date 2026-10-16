<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Ujian — {{ $kelas->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #1a1a1a; padding: 14pt; background: #fff; }

        .kartu {
            border: 1.5pt solid #1a3a5c; border-radius: 8pt; overflow: hidden;
            margin-bottom: 16pt; page-break-inside: avoid;
        }

        .kepala {
            background: #1a3a5c; color: #fff; padding: 10pt 12pt; text-align: center;
        }
        .kepala h2 { font-size: 14pt; letter-spacing: 1pt; margin-bottom: 2pt; }
        .kepala p { font-size: 8pt; opacity: 0.9; }

        .badan { padding: 12pt 14pt 10pt; }

        .nama-peserta { text-align: center; margin-bottom: 10pt; }
        .nama-peserta .nama { font-size: 15pt; font-weight: bold; color: #1a3a5c; }
        .nama-peserta .nisn { font-size: 9pt; color: #555; letter-spacing: 0.5pt; margin-top: 2pt; }
        .nama-peserta .kelas { font-size: 9pt; color: #555; margin-top: 2pt; }

        .jadwal { width: 100%; border-collapse: collapse; margin-bottom: 8pt; font-size: 8.5pt; }
        .jadwal th { background: #f0f6fd; color: #1a3a5c; text-align: left; padding: 5pt 6pt; border: 0.5pt solid #ccc; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.3pt; }
        .jadwal td { padding: 5pt 6pt; border: 0.5pt solid #ccc; vertical-align: top; }
        .jadwal td.token { font-family: monospace; font-weight: bold; letter-spacing: 1pt; }

        .login-box { background: #fafafa; border-radius: 4pt; padding: 6pt 8pt; margin-bottom: 8pt; font-size: 8.5pt; }
        .login-box .row { display: flex; justify-content: space-between; padding: 1pt 0; }
        .login-box .k { color: #666; }
        .login-box .v { font-weight: bold; font-family: monospace; }

        .ttd { display: flex; justify-content: space-between; font-size: 8pt; text-align: center; color: #444; margin-top: 10pt; }
        .ttd div { width: 45%; }
        .ttd .nama { margin-top: 32pt; border-top: 1pt solid #333; padding-top: 2pt; font-weight: bold; }

        .kaki {
            background: #f5f5f5; padding: 6pt 12pt; font-size: 7pt; color: #888; text-align: center;
            border-top: 0.5pt solid #ddd;
        }
        .kosong { text-align: center; color: #888; padding: 12pt; font-style: italic; }
    </style>
</head>
<body>
    @foreach ($pesertas as $siswa)
        <div class="kartu">
            <div class="kepala">
                <h2>KARTU PESERTA UJIAN</h2>
                <p>SMK INFORMATIKA SUMEDANG &bull; UJIAN SEKOLAH BERBASIS KOMPUTER</p>
            </div>
            <div class="badan">
                <div class="nama-peserta">
                    <div class="nama">{{ $siswa->nama_lengkap }}</div>
                    <div class="nisn">NISN: {{ $siswa->nisn }}</div>
                    <div class="kelas">Kelas: {{ $kelas->nama }}</div>
                </div>

                @if ($ujians->isNotEmpty())
                    <table class="jadwal">
                        <thead>
                            <tr>
                                <th style="width:4%">No</th>
                                <th style="width:28%">Ujian</th>
                                <th style="width:22%">Hari / Tanggal</th>
                                <th style="width:16%">Waktu</th>
                                <th style="width:14%">Ruang</th>
                                <th style="width:16%">Ttd Pengawas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ujians as $i => $ujian)
                                @php $sesi = $ujian->sesis->first(); @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $ujian->judul }}</strong><br><span style="color:#666;font-size:7.5pt;">{{ $ujian->guruKelas?->matpel?->nama ?? '' }}</span></td>
                                    <td>
                                        @if ($sesi)
                                            {{ $sesi->waktu_mulai->translatedFormat('l, d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($sesi)
                                            {{ $sesi->waktu_mulai->translatedFormat('H:i') }} &ndash; {{ $sesi->waktu_selesai->translatedFormat('H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $sesi?->ruang?->nama ?? '-' }}</td>
                                    <td style="height:32pt;"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="kosong">Belum ada ujian resmi yang diterbitkan untuk kelas ini.</div>
                @endif

                <div class="login-box">
                    <div class="row"><span class="k">Username</span><span class="v">{{ $siswa->nisn }}</span></div>
                    <div class="row"><span class="k">Password</span><span class="v">dibagikan wali kelas</span></div>
                </div>

                <div class="ttd">
                    <div>Peserta<div class="nama">{{ Str::limit($siswa->nama_lengkap, 25) }}</div></div>
                    <div>Wali Kelas<div class="nama">( ........................ )</div></div>
                </div>
            </div>
            <div class="kaki">
                Kartu ini wajib dibawa setiap ujian &bull; Token diumumkan pengawas saat ujian dimulai &bull; 1 kartu untuk semua ujian
            </div>
        </div>
    @endforeach
</body>
</html>
