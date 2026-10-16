# State Machine Diagram — KELAS DIGITAL IFSU

Diagram state memodelkan siklus hidup objek-objek yang memiliki status. Notasi: Mermaid `stateDiagram-v2`.

## 1. State — TugasPengumpulan (Pengumpulan Tugas Siswa)

```mermaid
stateDiagram-v2
    [*] --> BelumMengumpul : Tugas diterbitkan
    BelumMengumpul --> Terkumpul : POST /tugas/{tugas}/kumpul (file/jawaban teks)
    Terkumpul --> Terkumpul : submit ulang (update file/jawaban, submitted_at baru)
    Terkumpul --> Dinilai : Guru PUT /tugas/{tugas}/nilai
    Dinilai --> Terkumpul : Guru ubah nilai
    Dinilai --> [*] : Selesai
    BelumMengumpul --> [*] : Deadline lewat tanpa kumpul (tidak dinilai)
```

Keterangan atribut status: `submitted_at` (waktu terakhir submit), `nilai` (null hingga dinilai guru).

## 2. State — Siswa

```mermaid
stateDiagram-v2
    [*] --> Aktif : dibuat admin (is_aktif=true, status=aktif)
    Aktif --> Nonaktif : admin nonaktifkan (is_aktif=false)
    Aktif --> Keluar : status=keluar
    Nonaktif --> Aktif : admin aktifkan kembali
    Keluar --> [*]
    Aktif --> [*]
```

Keterangan: kolom `status` pada tabel `siswa` (string) menandai `aktif` / `keluar`; kolom `is_aktif` (boolean) dipakai untuk penyaringan.

## 3. State — SiswaKelas (Keanggotaan Siswa pada Kelas)

```mermaid
stateDiagram-v2
    [*] --> Aktif : Admin atur siswa ke kelas (active=true, pertama_masuk)
    Aktif --> Nonaktif : Naik kelas / pindah kelas (active=false)
    Aktif --> Nonaktif : Admin nonaktifkan manual
    Nonaktif --> Aktif : Admin aktifkan kembali
    Nonaktif --> [*] : Riwayat tersimpan
    Aktif --> [*]
```

Keterangan: relasi historis — satu siswa dapat memiliki banyak `siswa_kelas` untuk tahun ajaran berbeda; hanya yang `active=true` yang tampil sebagai kelas saat ini.

## 4. State — Kelas

```mermaid
stateDiagram-v2
    [*] --> Aktif : dibuat admin (active=true)
    Aktif --> Nonaktif : admin nonaktifkan (active=false)
    Nonaktif --> Aktif : admin aktifkan
    Nonaktif --> [*]
```

Keterangan: kelas induk (tingkat) dan rombongan (parent_id) sama-sama punya status aktif; penugasan & kenaikan kelas hanya memakai kelas aktif.

## 5. State — Penilaian

```mermaid
stateDiagram-v2
    [*] --> Aktif : dibuat admin/guru (aktif=true)
    Aktif --> Nonaktif : dinonaktifkan (aktif=false)
    Nonaktif --> Aktif : diaktifkan kembali
    Aktif --> [*]
```

## 6. State — TahunAjaran

```mermaid
stateDiagram-v2
    [*] --> Nonaktif : dibuat admin (active=false)
    Nonaktif --> Aktif : admin set sebagai tahun ajaran aktif (active=true)
    Aktif --> Nonaktif : tahun ajaran baru diaktifkan (satu-satunya active)
    Aktif --> [*]
```

Keterangan: aplikasi memakai tepat satu tahun ajaran aktif sebagai konteks default di seluruh halaman (dishare via `BaseAppController` / Inertia props).

## 7. State — Sesi Autentikasi

```mermaid
stateDiagram-v2
    [*] --> Guest : tanpa session
    Guest --> Authenticated : login sukses (email/password atau Google)
    Authenticated --> Guest : logout (POST /logout, session dihapus)
    Authenticated --> Guest : session expire
    Guest --> [*]
```

Keterangan: middleware `auth` melindungi seluruh route `admin.*` dan `app.*`; `AdminOnly`/`AppOnly` membatasi peran.

## 8. State — Tugas

```mermaid
stateDiagram-v2
    [*] --> Diterbitkan : guru create (tanggal_terbit=now)
    Diterbitkan --> Diterbitkan : guru edit (judul, deadline, poin, file)
    Diterbitkan --> Kadaluarsa : deadline lewat
    Kadaluarsa --> Diterbitkan : guru perpanjang deadline
    Diterbitkan --> [*] : guru hapus
    Kadaluarsa --> [*] : guru hapus
```

## 9. State — Materi

```mermaid
stateDiagram-v2
    [*] --> Publikasi : guru create (judul, konten, file)
    Publikasi --> Publikasi : guru edit
    Publikasi --> [*] : guru hapus
```