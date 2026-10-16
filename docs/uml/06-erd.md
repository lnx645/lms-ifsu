# ERD — KELAS DIGITAL IFSU

Entity Relationship Diagram seluruh tabel pada basis data PostgreSQL aplikasi (berdasarkan skema aktual `database/schema/pgsql-schema.sql`). Notasi: Mermaid `erDiagram`.

## 1. Diagram ERD Keseluruhan

```mermaid
erDiagram
    USERS ||--o| GURUS : "guru_id"
    USERS ||--o| SISWA : "nisn"
    GURUS ||--o{ GURU_KELAS : "mengampu"
    GURUS ||--o{ MATERIS : "membuat"
    GURUS ||--o{ TUGASES : "membuat"
    GURUS ||--o{ DETAIL_PENILAIAN : "menilai"
    SISWA ||--o{ SISWA_KELAS : "terdaftar"
    SISWA ||--o{ TUGAS_PENGUMPULANS : "mengumpulkan"
    SISWA ||--o{ DETAIL_PENILAIAN : "menerima"
    KELAS ||--o{ KELAS : "parent_id"
    KELAS ||--o{ SISWA_KELAS : "berisi"
    KELAS ||--o{ GURU_KELAS : "diampu"
    JURUSANS ||--o{ KELAS : "menaungi"
    MATPELS ||--o{ GURU_KELAS : "diajarkan"
    TAHUN_AJARAN ||--o{ SISWA_KELAS : "masa"
    TAHUN_AJARAN ||--o{ GURU_KELAS : "masa"
    GURU_KELAS ||--o{ MATERIS : "target"
    GURU_KELAS ||--o{ TUGASES : "target"
    GURU_KELAS ||--o{ DETAIL_PENILAIAN : "target"
    TUGASES ||--o{ TUGAS_PENGUMPULANS : "diterima"
    TUGASES }o--o| PENILAIAN : "penilaian_id"
    PENILAIAN ||--o{ DETAIL_PENILAIAN : "menghasilkan"
    PENILAIAN ||--o{ PENILAIAN_KELAS : "menjangkau"
    PENILAIAN_KELAS }o--|| KELAS : "kelas_id"

    USERS {
        bigint id PK
        varchar(255) name
        varchar(255) email
        varchar(255) password
        varchar(255) google_id
        varchar(255) role
        varchar(10) nisn FK
        bigint guru_id FK
        boolean is_admin
        timestamp email_verified_at
    }
    GURUS {
        bigint id PK
        char(18) nip
        varchar(50) nama_lengkap
        varchar(255) jenis_kelamin
        text alamat
        varchar(255) foto_profil
        varchar(50) pendidikan_terakhir
        boolean is_aktif
    }
    SISWA {
        varchar(10) nisn PK
        char(10) nis
        varchar(100) nama_lengkap
        varchar(100) tempat_lahir
        date tanggal_lahir
        varchar(255) jenis_kelamin
        text alamat
        varchar(255) foto_profil
        varchar(20) status
        boolean is_aktif
    }
    KELAS {
        bigint id PK
        varchar(255) nama
        text deskripsi
        bigint guru_id
        bigint parent_id FK
        bigint jurusan_id FK
        varchar(20) tingkat
        boolean active
    }
    JURUSANS {
        bigint id PK
        varchar(255) name
        varchar(255) kode
    }
    MATPELS {
        bigint id PK
        varchar(255) name
        text description
    }
    TAHUN_AJARAN {
        bigint id PK
        varchar(255) name
        boolean active
    }
    SISWA_KELAS {
        bigint id PK
        varchar(10) siswa_nisn FK
        bigint kelas_id FK
        bigint tahun_ajaran_id FK
        boolean active
        boolean pertama_masuk
    }
    GURU_KELAS {
        bigint id PK
        bigint guru_id FK
        bigint kelas_id FK
        bigint matpel_id FK
        bigint tahun_ajaran_id FK
        boolean aktif
    }
    MATERIS {
        bigint id PK
        bigint guru_id FK
        bigint guru_kelas_id FK
        varchar(255) judul
        text deskripsi
        text konten
        varchar(255) file_path
        varchar(255) file_name
        bigint file_size
        varchar(255) mime_type
    }
    TUGASES {
        bigint id PK
        bigint guru_id FK
        bigint guru_kelas_id FK
        bigint penilaian_id FK
        varchar(255) judul
        text deskripsi
        timestamp tanggal_terbit
        timestamp deadline
        varchar(255) file_path
        varchar(255) file_name
        bigint file_size
        varchar(255) mime_type
        varchar(20) jenis_pengumpulan
        integer poin
    }
    TUGAS_PENGUMPULANS {
        bigint id PK
        bigint tugas_id FK
        varchar(10) siswa_nisn FK
        varchar(255) file_path
        varchar(255) file_name
        bigint file_size
        varchar(255) mime_type
        text jawaban_teks
        timestamp submitted_at
        numeric(5,2) nilai
    }
    PENILAIAN {
        bigint id PK
        varchar(255) nama
        text deskripsi
        varchar(255) tipe
        varchar(255) sumber
        integer nilai_maks
        numeric(5,2) bobot
        boolean aktif
    }
    DETAIL_PENILAIAN {
        bigint id PK
        bigint penilaian_id FK
        varchar(10) siswa_nisn FK
        bigint guru_id FK
        bigint guru_kelas_id FK
        bigint tahun_ajaran_id FK
        numeric(5,2) nilai
        varchar(255) sumber
        text keterangan
    }
    PENILAIAN_KELAS {
        bigint penilaian_id PK,FK
        bigint kelas_id PK,FK
    }
```

## 2. Ringkasan Tabel

| Tabel | PK | FK | Keterangan |
|-------|----|----|------------|
| `users` | id | nisn, guru_id | Akun login semua peran (admin/guru/siswa) |
| `gurus` | id | – | Data profil guru |
| `siswa` | nisn | – | Data profil siswa (PK nisn) |
| `kelas` | id | parent_id, jurusan_id | Kelas induk + rombongan |
| `jurusans` | id | – | Jurusan (RPL, DKV, IND, AXIO) |
| `matpels` | id | – | Mata pelajaran |
| `tahun_ajaran` | id | – | Tahun ajaran (satu aktif) |
| `siswa_kelas` | id | siswa_nisn, kelas_id, tahun_ajaran_id | Keanggotaan historis siswa per kelas |
| `guru_kelas` | id | guru_id, kelas_id, matpel_id, tahun_ajaran_id | Penugasan mengajar guru |
| `materis` | id | guru_id, guru_kelas_id | Materi pembelajaran |
| `tugases` | id | guru_id, guru_kelas_id, penilaian_id | Tugas |
| `tugas_pengumpulans` | id | tugas_id, siswa_nisn | Pengumpulan tugas siswa |
| `penilaian` | id | – | Jenis penilaian (UTS, PAS, dsb.) |
| `detail_penilaian` | id | penilaian_id, siswa_nisn, guru_id, guru_kelas_id, tahun_ajaran_id | Nilai per siswa |
| `penilaian_kelas` | penilaian_id+kelas_id | penilaian_id, kelas_id | Pivot penilaian ↔ kelas |

## 3. Catatan Desain

- **Siswa**: `nisn` (10 digit) adalah primary key natural, bukan auto-increment.
- **Kelas bertingkat**: kolom `parent_id` menghubungkan rombongan ke kelas induk tingkat (X/XI/XII). Kolom `tingkat` menyimpan label tingkat (X, XI, XII) atau null; fallback `Kelas::tingkatDariNama()` menebak tingkat dari nama.
- **Historis per tahun ajaran**: `siswa_kelas` dan `guru_kelas` menyimpan `tahun_ajaran_id` sehingga riwayat perpindahan siswa & penugasan guru selalu tercatat.
- **Index pendukung**: indeks ditambahkan untuk `kelas.parent_id`, `siswa_kelas.kelas_id`, `siswa_kelas.siswa_nisn`, dan kolom FK lainnya guna mempercepat proses naik kelas (lihat migrasi `add_naik_kelas_indexes`).