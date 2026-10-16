<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Ujian — {{ $ujian->judul }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #1a1a1a; padding: 14pt; background: #fff; }

        .kartu {
            width: 48%; display: inline-block; vertical-align: top; margin-right: 2%; margin-bottom: 14pt;
            border: 1.5pt solid #1a3a5c; border-radius: 8pt; overflow: hidden;
            page-break-inside: avoid;
        }
        .kartu:nth-child(even) { margin-right: 0; }

        /* Header biru navy ala kartu resmi */
        .kepala {
            background: #1a3a5c;
            color: #fff; padding: 8pt 10pt; text-align: center;
        }
        .kepala h2 { font-size: 12pt; letter-spacing: 1pt; margin-bottom: 2pt; }
        .kepala p { font-size: 7.5pt; opacity: 0.9; }

        .badan { padding: 10pt 12pt 8pt; }

        /* Nama besar di atas */
        .nama-peserta { text-align: center; margin-bottom: 8pt; }
        .nama-peserta .nama { font-size: 13pt; font-weight: bold; color: #1a3a5c; }
        .nama-peserta .nisn { font-size: 8.5pt; color: #555; letter-spacing: 0.5pt; margin-top: 2pt; }

        /* Grid info */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
        .info td { padding: 3pt 0; font-size: 8.5pt; vertical-align: top; border-bottom: 0.5pt dotted #ddd; }
        .info td.label { width: 30%; color: #666; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.3pt; }
        .info td.nilai { font-weight: 600; }

        /* Token box menonjol */
        .token-box {
            background: #f0f6fd; border: 1pt dashed #2b567a; border-radius: 4pt;
            text-align: center; padding: 6pt; margin: 8pt 0;
        }
        .token-box .lbl { font-size: 7pt; color: #666; text-transform: uppercase; letter-spacing: 0.5pt; }
        .token-box .val { font-size: 16pt; font-weight: bold; color: #1a3a5c; letter-spacing: 3pt; }

        /* Kredensial login */
        .login-box { background: #fafafa; border-radius: 4pt; padding: 6pt 8pt; margin-bottom: 8pt; font-size: 8pt; }
        .login-box .row { display: flex; justify-content: space-between; padding: 1pt 0; }
        .login-box .k { color: #666; }
        .login-box .v { font-weight: bold; font-family: monospace; }

        .ttd { display: flex; justify-content: space-between; font-size: 7.5pt; text-align: center; color: #444; margin-top: 6pt; }
        .ttd div { width: 45%; }
        .ttd .nama { margin-top: 28pt; border-top: 1pt solid #333; padding-top: 2pt; font-weight: bold; }

        .kaki {
            background: #f5f5f5; padding: 5pt 10pt; font-size: 6.5pt; color: #888; text-align: center;
            border-top: 0.5pt solid #ddd;
        }
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
                </div>

                <table class="info">
                    <tr><td class="label">Ujian</td><td class="nilai">{{ $ujian->judul }}</td></tr>
                    <tr><td class="label">Kelas</td><td class="nilai">{{ $ujian->guruKelas?->kelas?->nama ?? '-' }}</td></tr>
                </table>

                <div class="login-box">
                    <div class="row"><span class="k">Username</span><span class="v">{{ $siswa->nisn }}</span></div>
                    <div class="row"><span class="k">Password</span><span class="v">{{ $siswa->password_ujian_plain ?? '—' }}</span></div>
                </div>

                <div class="ttd">
                    <div>Peserta<div class="nama">{{ Str::limit($siswa->nama_lengkap, 20) }}</div></div>
                    <div>Pengawas<div class="nama">( ........................ )</div></div>
                </div>
            </div>
            <div class="kaki">
                Token diumumkan pengawas saat ujian dimulai &bull; Kartu ini wajib dibawa saat ujian
            </div>
        </div>
    @endforeach
</body>
</html>
