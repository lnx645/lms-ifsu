# Sequence Diagram — KELAS DIGITAL IFSU

Diagram urutan (sequence) menggambarkan interaksi antar objek sepanjang waktu untuk alur-alur utama aplikasi. Notasi: Mermaid `sequenceDiagram`.

## 1. Autentikasi — Login Email/Password

```mermaid
sequenceDiagram
    autonumber
    actor U as User
    participant B as Browser (Svelte)
    participant C as AuthenticatedSessionController
    participant R as RateLimiter
    participant D as User Model
    participant S as Session

    U->>B: isi email & password, klik Login
    B->>C: POST /login (Inertia)
    C->>R: cek throttle (5x/menit per email+IP)
    R-->>C: diizinkan / ditolak (429)
    C->>D: attempt(credentials, remember)
    alt kredensial benar
        D-->>C: user terautentikasi
        C->>S: regenerasi session id
        C->>S: simpan role & data user
        C-->>B: redirect ke app.dashboard (sesuai peran)
    else kredensial salah
        D-->>C: gagal
        C-->>B: kembali ke /login + error validasi
    end
```

## 2. Autentikasi — Login Google OAuth

```mermaid
sequenceDiagram
    autonumber
    actor U as User
    participant B as Browser
    participant SC as SocialiteController
    participant GO as Google OAuth Server
    participant D as User Model

    U->>B: klik "Masuk dengan Google"
    B->>SC: GET /auth/google/redirect
    SC-->>B: redirect ke URL OAuth Google
    B->>GO: permintaan izin akun Google
    GO-->>B: callback code (GET /auth/google/callback)
    B->>SC: callback(code)
    SC->>GO: tukar code → profil Google
    GO-->>SC: email + google_id
    SC->>D: firstOrCreate(email, google_id)
    alt user baru (email tidak terdaftar)
        D-->>SC: login gagal → toast error
        SC-->>B: redirect /login
    else user terdaftar
        SC->>S: login + regenerasi session
        SC-->>B: redirect app.dashboard
    end
```

## 3. Admin — Kelola Data Master (CRUD Jurusan)

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser (Svelte)
    participant C as JurusanController
    participant V as Validasi (FormRequest/inline)
    participant D as Jurusan Model
    participant T as Toast

    A->>B: buka halaman admin/jurusan
    B->>C: GET admin/jurusan (Inertia)
    C->>D: Jurusan::all()
    D-->>C: daftar jurusan
    C-->>B: render Index.svelte + props

    A->>B: isi form tambah & submit
    B->>C: POST admin/jurusan
    C->>V: validasi (name, kode)
    alt valid
        C->>D: Jurusan::create()
        D-->>C: jurusan baru
        C->>T: Toast::success
        C-->>B: redirect (PRG) ke index
    else tidak valid
        V-->>C: error bag
        C-->>B: redirect kembali + errors
    end

    A->>B: klik hapus
    B->>C: DELETE admin/jurusan/{jurusan}
    C->>D: delete()
    D-->>C: terhapus
    C->>T: Toast::info
    C-->>B: redirect index
```

> Pola yang sama (GET list → POST create → PUT update → DELETE destroy + Toast + PRG) dipakai oleh controller master lainnya: **Kelas, Matpel, TahunAjaran, Siswa, Pengajar, Penilaian, AkunAdmin**.

## 4. Admin — Kelola Akun Guru & Atur Penugasan

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser
    participant C as AkunGuruController
    participant D as Guru Model
    participant U as User Model
    participant T as Toast

    A->>B: buka tab akun guru
    B->>C: GET admin/pengajar/{guru}/akun
    C->>D: Guru::with(user)->find()
    C-->>B: render AturAkun.svelte

    A->>B: submit form akun (email, password)
    B->>C: POST admin/pengajar/{guru}/akun
    C->>U: firstOrCreate email → update user (guru_id, role=guru)
    C->>T: Toast::success
    C-->>B: redirect (PRG)

    A->>B: buka penugasan guru
    B->>C: GET admin/pengajar/{guru}/penugasan
    C->>D: Guru::with('guruKelas.kelas','guruKelas.matpel')->find()
    C-->>B: render AturGuruKelas.svelte

    A->>B: tambah penugasan (kelas + matpel + tahun ajaran)
    B->>C: POST admin/pengajar/{guru}/penugasan
    C->>G: GuruKelas::create()
    C->>T: Toast::success
    C-->>B: redirect
```

## 5. Admin — Atur Siswa ke Kelas

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser
    participant C as SiswaKelasController
    participant D as Siswa Model
    participant SK as SiswaKelas Model
    participant T as Toast

    A->>B: buka halaman admin/siswa/{siswa}/kelas
    B->>C: GET admin/siswa/{siswa}/kelas
    C->>D: Siswa::with('siswaKelas.kelas.tahunAjaran')->find()
    C-->>B: render AturSiswaKelas.svelte

    A->>B: pilih kelas + tahun ajaran, submit
    B->>C: POST admin/siswa/{siswa}/kelas
    C->>SK: SiswaKelas::create(siswa_nisn, kelas_id, tahun_ajaran_id, active)
    C->>T: Toast::success
    C-->>B: redirect

    A->>B: ubah status aktif / pindah kelas
    B->>C: PUT admin/siswa/{siswa}/kelas/{siswaKelas}
    C->>SK: update(active / kelas_id)
    C->>T: Toast::success
    C-->>B: redirect
```

## 6. Admin — Kelola Penilaian & Detail Penilaian

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser
    participant C as PenilaianController
    participant DC as DetailPenilaianController
    participant P as Penilaian Model
    participant D as DetailPenilaian Model
    participant T as Toast

    A->>B: buka admin/penilaian
    B->>C: GET admin/penilaian
    C->>P: Penilaian::all() (dengan kelas terkait)
    C-->>B: render Penilaian/Index.svelte

    A->>B: tambah penilaian (nama, tipe, nilai_maks, bobot, kelas)
    B->>C: POST admin/penilaian
    C->>P: create + sync kelas (penilaian_kelas)
    C->>T: Toast::success
    C-->>B: redirect

    A->>B: buka detail penilaian (filter penugasan)
    B->>DC: GET admin/penilaian/{p}/penugasan?kelas&matpel
    DC->>P: Penilaian::find()
    DC->>D: filter DetailPenilaian per guru_kelas & tahun ajaran
    DC-->>B: render DetailPenilaian/Filter.svelte

    A->>B: input nilai siswa
    B->>DC: POST admin/penilaian/{p}/penugasan/{g}/siswa/{s}
    DC->>D: DetailPenilaian::updateOrCreate(nilai, sumber, keterangan)
    DC->>T: Toast::success
    DC-->>B: redirect
```

## 7. Guru — Kelola Materi

```mermaid
sequenceDiagram
    autonumber
    actor G as Guru
    participant B as Browser
    participant C as MateriController (Guru)
    participant V as Validasi file (mime, ukuran)
    participant D as Materi Model
    participant T as Toast

    G->>B: buka app/guru/materi
    B->>C: GET app/guru/materi
    C->>D: Materi milik guru (dengan guruKelas.kelas)
    C-->>B: render Materi/Index.svelte

    G->>B: isi form (judul, deskripsi, konten, kelas, file)
    B->>C: POST app/guru/materi
    C->>V: validasi file (materi/ dir di public disk)
    alt valid
        C->>D: Materi::create()
        C->>T: Toast::success
        C-->>B: redirect (PRG)
    else file tidak valid
        V-->>C: error bag
        C-->>B: redirect + errors
    end

    G->>B: buka katalog materi sekolah
    B->>C: GET app/guru/materi/katalog
    C->>D: Materi semua guru (dengan pemilik)
    C-->>B: render katalog

    G->>B: klik salin materi
    B->>C: POST app/guru/materi/salin
    C->>D: duplikasi record ke guru_kelas milik guru
    C->>T: Toast::success
    C-->>B: redirect materi

    G->>B: klik unduh
    B->>C: GET app/guru/materi/{materi}/unduh
    C-->>B: stream file dari public disk
```

## 8. Guru — Buat Tugas & Menilai Pengumpulan

```mermaid
sequenceDiagram
    autonumber
    actor G as Guru
    participant B as Browser
    participant C as TugasController (Guru)
    participant D as Tugas Model
    participant P as TugasPengumpulan Model
    participant T as Toast

    G->>B: buka app/guru/tugas
    B->>C: GET app/guru/tugas
    C->>D: Tugas milik guru
    C-->>B: render Tugas/Index.svelte

    G->>B: buat tugas (judul, deskripsi, deadline, jenis_pengumpulan, poin, kelas, penilaian, file)
    B->>C: POST app/guru/tugas
    C->>D: Tugas::create()
    C->>T: Toast::success
    C-->>B: redirect

    G->>B: buka daftar pengumpulan
    B->>C: GET app/guru/tugas/{tugas}/pengumpulan
    C->>P: TugasPengumpulan dengan siswa
    C-->>B: render Tugas/Pengumpulan.svelte

    G->>B: isi nilai per pengumpulan
    B->>C: PUT app/guru/tugas/{tugas}/nilai
    C->>P: update(nilai)
    C->>T: Toast::success
    C-->>B: redirect
```

## 9. Siswa — Lihat Materi

```mermaid
sequenceDiagram
    autonumber
    actor S as Siswa
    participant B as Browser
    participant C as MateriController (Siswa)
    participant D as Materi Model
    participant SK as SiswaKelas Model

    S->>B: buka app/materi
    B->>C: GET app/materi
    C->>SK: cari kelas aktif siswa (tahun ajaran aktif)
    C->>D: Materi untuk guru_kelas milik kelas siswa
    C-->>B: render Materi/Index.svelte

    S->>B: klik detail materi
    B->>C: GET app/materi/{materi}
    C-->>B: render Materi/Detail.svelte (konten + file)

    S->>B: klik pratinjau
    B->>C: GET app/materi/{materi}/lihat
    C-->>B: tampilkan file/pratinjau konten

    S->>B: klik unduh
    B->>C: GET app/materi/{materi}/unduh
    C-->>B: stream file
```

## 10. Siswa — Kumpulkan Tugas

```mermaid
sequenceDiagram
    autonumber
    actor S as Siswa
    participant B as Browser
    participant C as TugasController (Siswa)
    participant V as Validasi (file/jawaban teks)
    participant D as Tugas Model
    participant P as TugasPengumpulan Model
    participant T as Toast

    S->>B: buka app/tugas
    B->>C: GET app/tugas
    C->>D: Tugas untuk kelas siswa
    C-->>B: render Tugas/Index.svelte

    S->>B: klik detail tugas
    B->>C: GET app/tugas/{tugas}
    C->>P: cek status pengumpulan siswa
    C-->>B: render Tugas/Detail.svelte

    S->>B: unggah file / isi jawaban teks, submit
    B->>C: POST app/tugas/{tugas}/kumpul
    C->>V: validasi file (tugas-kumpul/ dir)
    alt belum pernah mengumpul
        C->>P: TugasPengumpulan::create(submitted_at=now)
        C->>T: Toast::success
        C-->>B: redirect detail
    else sudah mengumpul
        C->>P: update file/jawaban + submitted_at
        C->>T: Toast::success
        C-->>B: redirect detail
    end
```

## 11. Siswa — Lihat Nilai

```mermaid
sequenceDiagram
    autonumber
    actor S as Siswa
    participant B as Browser
    participant C as PenilaianController (Siswa)
    participant D as DetailPenilaian Model

    S->>B: buka app/nilai
    B->>C: GET app/nilai
    C->>D: DetailPenilaian untuk nisn siswa (dengan penilaian & guru)
    C-->>B: render Penilaian/Index.svelte (daftar nilai per penilaian)
```

## 12. Naik Kelas — Preview & Execute

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser
    participant C as NaikKelasController
    participant S as NaikKelasService
    participant K as Kelas Model
    participant SK as SiswaKelas Model
    participant TA as TahunAjaran Model
    participant T as Toast

    A->>B: buka admin/naik-kelas
    B->>C: GET admin/naik-kelas
    C->>K: Kelas semua tingkatan (induk & rombongan)
    C->>TA: TahunAjaran::active()
    C-->>B: render NaikKelas/Index.svelte

    A->>B: pilih kelas sumber & kelas tujuan + tahun ajaran, klik Preview
    B->>C: POST admin/naik-kelas/preview
    C->>S: preview(sumber, tujuan, tahunAjaran)
    S->>S: validasi tingkat (naik = +1, pindah = sama)
    S->>SK: siswa aktif di kelas sumber
    S-->>C: daftar siswa yang akan dipindah
    C-->>B: render hasil preview (Inertia)

    A->>B: klik Konfirmasi Naik Kelas
    B->>C: POST admin/naik-kelas/execute
    C->>S: execute(sumber, tujuan, tahunAjaran)
    S->>SK: nonaktifkan SiswaKelas lama
    S->>SK: buat SiswaKelas baru (tahun ajaran baru, active)
    S->>SK: tandai pertama_masuk pada siswa pindahan? (sesuai aturan)
    S-->>C: jumlah siswa dipindah
    C->>T: Toast::success(jumlah siswa)
    C-->>B: redirect index (PRG)
```

## 13. Guru — Input & Rekap Penilaian

```mermaid
sequenceDiagram
    autonumber
    actor G as Guru
    participant B as Browser
    participant C as PenilaianController (Guru)
    participant D as DetailPenilaian Model
    participant T as Toast

    G->>B: buka app/guru/penilaian
    B->>C: GET app/guru/penilaian
    C->>D: penilaian terkait penugasan guru
    C-->>B: render Penilaian/Index.svelte

    G->>B: pilih penilaian + penugasan (kelas)
    B->>C: GET app/guru/penilaian/{penilaian}/{guruKelas}
    C->>D: siswa kelas + nilai saat ini
    C-->>B: render Penilaian/Input.svelte

    G->>B: isi nilai semua siswa, submit
    B->>C: POST app/guru/penilaian/{penilaian}/{guruKelas}
    C->>D: bulk updateOrCreate nilai per siswa
    C->>T: Toast::success
    C-->>B: redirect

    G->>B: buka rekap
    B->>C: GET app/guru/penilaian/rekap
    C->>D: agregasi nilai per penugasan/kelas
    C-->>B: render Penilaian/Rekap.svelte
```