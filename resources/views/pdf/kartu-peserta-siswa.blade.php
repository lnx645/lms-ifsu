<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Ujian — {{ $siswa->nama_lengkap }}</title>
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

        .login-box { background: #fff3cd; border: 1pt solid #ffc107; border-radius: 4pt; padding: 8pt 10pt; margin-bottom: 8pt; }
        .login-box .judul { font-size: 8pt; font-weight: bold; color: #856404; margin-bottom: 4pt; text-transform: uppercase; letter-spacing: 0.5pt; }
        .login-box .row { display: flex; justify-content: space-between; padding: 2pt 0; font-size: 9pt; }
        .login-box .k { color: #666; }
        .login-box .v { font-weight: bold; font-family: monospace; font-size: 12pt; letter-spacing: 2pt; color: #1a3a5c; }

        .ttd { display: flex; justify-content: space-between; font-size: 8pt; text-align: center; color: #444; margin-top: 10pt; }
        .ttd div { width: 45%; }
        .ttd .nama { margin-top: 32pt; border-top: 1pt solid #333; padding-top: 2pt; font-weight: bold; }

        .kaki {
            background: #f5f5f5; padding: 6pt 10pt; font-size: 7pt; color: #888; text-align: center;
            border-top: 0.5pt solid #ddd;
        }
    </style>
</head>
<body>
    <div class="kartu">
        <div class="kepala">
            <h2>KARTU PESERTA UJIAN</h2>
            <p>SMK INFORMATIKA SUMEDANG &bull; UJIAN SEKOLAH BERBASIS KOMPUTER</p>
        </div>
        <div class="badan">
            <div class="nama-peserta">
                <div class="nama">{{ $siswa->nama_lengkap }}</div>
                <div class="nisn">NISN: {{ $siswa->nisn }}</div>
                <div class="kelas">Kelas: {{ $kelas?->nama ?? '-' }}</div>
            </div>

            @if ($ujians->isNotEmpty())
                <table class="jadwal">
                    <thead>
                        <tr><th>Ujian</th><th>Jadwal</th><th>Ruang</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($ujians as $ujian)
                            @foreach ($ujian->sesis as $sesi)
                                <tr>
                                    <td><strong>{{ $ujian->judul }}</strong><br><small>{{ $ujian->guruKelas?->matpel?->name }}</small></td>
                                    <td>{{ $sesi->nama }}<br><small>{{ $sesi->waktu_mulai?->translatedFormat('d M Y H:i') }} – {{ $sesi->waktu_selesai?->translatedFormat('H:i') }}</small></td>
                                    <td>{{ $sesi->ruang?->nama ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align:center; color:#888; margin-bottom:8pt;">Belum ada jadwal ujian resmi.</p>
            @endif

            <div class="login-box">
                <div class="judul"><i>🔐</i> Login Ujian — Jaga Kerahasiaannya!</div>
                <div class="row"><span class="k">Username (NISN)</span><span class="v">{{ $siswa->nisn }}</span></div>
                <div class="row"><span class="k">Password</span><span class="v">{{ $siswa->password_ujian_plain ?? '— (hubungi panitia)' }}</span></div>
            </div>
            <p style="font-size:7.5pt; color:#856404; margin-bottom:6pt;">
                Login di: <strong>/ujian/login</strong> &bull; Token diumumkan pengawas saat ujian dimulai.
            </p>

            <div class="ttd">
                <div>Peserta<div class="nama">{{ Str::limit($siswa->nama_lengkap, 25) }}</div></div>
                <div>Wali Kelas<div class="nama">( ........................ )</div></div>
            </div>
        </div>
        <div class="kaki">
            Simpan kartu ini baik-baik &bull; Jangan bagikan password ke orang lain
        </div>
    </div>
</body>
</html>
