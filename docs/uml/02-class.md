# Class Diagram — KELAS DIGITAL IFSU

Diagram kelas memodelkan seluruh **model Eloquent** di `app/Models/` beserta relasi dan atributnya (berdasarkan skema basis data PostgreSQL). Diagram ditulis dalam notasi Mermaid `classDiagram`.

## 1. Diagram Kelas Keseluruhan

```mermaid
classDiagram
    direction LR

    class User {
        +bigint id
        +string name
        +string email
        +string password
        +string google_id
        +string role
        +string nisn
        +bigint guru_id
        +boolean is_admin
        +accessor role() : string
        +belongsTo: guru()
        +roleLabel() : string
    }

    class Admin {
        +tipe peran: admin
    }

    class Guru {
        +bigint id
        +char(18) nip
        +string nama_lengkap
        +string jenis_kelamin
        +text alamat
        +string foto_profil
        +string pendidikan_terakhir
        +boolean is_aktif
        +hasMany: guruKelas()
        +hasMany: materis()
        +hasMany: tugases()
        +hasMany: detailPenilaian()
    }

    class Siswa {
        +string nisn <<PK>>
        +char(10) nis
        +string nama_lengkap
        +string tempat_lahir
        +date tanggal_lahir
        +string jenis_kelamin
        +text alamat
        +string foto_profil
        +string status
        +boolean is_aktif
        +hasMany: siswaKelas()
        +hasMany: pengumpulan()
    }

    class Kelas {
        +bigint id
        +string nama
        +text deskripsi
        +bigint guru_id
        +bigint parent_id
        +bigint jurusan_id
        +string tingkat
        +boolean active
        +belongsTo: jurusan()
        +belongsTo: parent()
        +hasMany: anakKelas()
        +hasMany: siswaKelas()
        +hasMany: guruKelas()
        +tingkatSekarang() : string
        +tingkatDariNama() : string
    }

    class Jurusan {
        +bigint id
        +string name
        +string kode
        +hasMany: kelas()
    }

    class Matpel {
        +bigint id
        +string name
        +text description
        +hasMany: guruKelas()
    }

    class TahunAjaran {
        +bigint id
        +string name
        +boolean active
        +hasMany: siswaKelas()
        +hasMany: guruKelas()
    }

    class SiswaKelas {
        +bigint id
        +string siswa_nisn
        +bigint kelas_id
        +bigint tahun_ajaran_id
        +boolean active
        +boolean pertama_masuk
        +belongsTo: siswa()
        +belongsTo: kelas()
        +belongsTo: tahunAjaran()
    }

    class GuruKelas {
        +bigint id
        +bigint guru_id
        +bigint kelas_id
        +bigint matpel_id
        +bigint tahun_ajaran_id
        +boolean aktif
        +belongsTo: guru()
        +belongsTo: kelas()
        +belongsTo: matpel()
        +belongsTo: tahunAjaran()
    }

    class Materi {
        +bigint id
        +bigint guru_id
        +bigint guru_kelas_id
        +string judul
        +text deskripsi
        +text konten
        +string file_path
        +string file_name
        +bigint file_size
        +string mime_type
        +belongsTo: guru()
        +belongsTo: guruKelas()
    }

    class Tugas {
        +bigint id
        +bigint guru_id
        +bigint guru_kelas_id
        +bigint penilaian_id
        +string judul
        +text deskripsi
        +timestamp tanggal_terbit
        +timestamp deadline
        +string file_path
        +string file_name
        +string jenis_pengumpulan
        +integer poin
        +hasMany: pengumpulan()
        +belongsTo: guru()
        +belongsTo: guruKelas()
        +belongsTo: penilaian()
    }

    class TugasPengumpulan {
        +bigint id
        +bigint tugas_id
        +string siswa_nisn
        +string file_path
        +string file_name
        +bigint file_size
        +string mime_type
        +text jawaban_teks
        +timestamp submitted_at
        +numeric(5,2) nilai
        +belongsTo: tugas()
        +belongsTo: siswa()
    }

    class Penilaian {
        +bigint id
        +string nama
        +text deskripsi
        +string tipe
        +string sumber
        +integer nilai_maks
        +numeric(5,2) bobot
        +boolean aktif
        +hasMany: detailPenilaian()
        +belongsToMany: kelas()
    }

    class DetailPenilaian {
        +bigint id
        +bigint penilaian_id
        +string siswa_nisn
        +bigint guru_id
        +bigint guru_kelas_id
        +bigint tahun_ajaran_id
        +numeric(5,2) nilai
        +string sumber
        +text keterangan
        +belongsTo: penilaian()
        +belongsTo: siswa()
        +belongsTo: guru()
        +belongsTo: guruKelas()
    }

    class PenilaianKelas {
        +bigint penilaian_id
        +bigint kelas_id
    }

    User <|-- Admin : <<peran role>>
    User "1" o-- "0..1" Guru : guru_id
    User "1" o-- "0..1" Siswa : nisn
    Guru "1" o-- "0..*" GuruKelas : mengampu
    Guru "1" o-- "0..*" Materi : membuat
    Guru "1" o-- "0..*" Tugas : membuat
    Guru "1" o-- "0..*" DetailPenilaian : menilai
    Siswa "1" o-- "0..*" SiswaKelas : terdaftar
    Siswa "1" o-- "0..*" TugasPengumpulan : mengumpulkan
    Siswa "1" o-- "0..*" DetailPenilaian : menerima
    Kelas "1" o-- "0..*" SiswaKelas : berisi
    Kelas "1" o-- "0..*" GuruKelas : diampu
    Kelas "1" o-- "0..*" Kelas : parent_id
    Kelas "1" o-- "0..*" PenilaianKelas : dihubungi
    Jurusan "1" o-- "0..*" Kelas : menaungi
    Matpel "1" o-- "0..*" GuruKelas : diajarkan
    TahunAjaran "1" o-- "0..*" SiswaKelas : masa
    TahunAjaran "1" o-- "0..*" GuruKelas : masa
    GuruKelas "1" o-- "0..*" Materi : menjadi target
    GuruKelas "1" o-- "0..*" Tugas : menjadi target
    GuruKelas "1" o-- "0..*" DetailPenilaian : menjadi target
    Tugas "1" o-- "0..*" TugasPengumpulan : diterima
    Tugas "1" o-- "0..1" Penilaian : dikaitkan
    Penilaian "1" o-- "0..*" DetailPenilaian : menghasilkan
    Penilaian "1" o-- "0..*" PenilaianKelas : menjangkau
    PenilaianKelas "1" o-- "1" Kelas : key
```

## 2. Diagram Kelas — Sisi Autentikasi & Peran

```mermaid
classDiagram
    class User {
        +string role
        +boolean is_admin
        +accessor role()
        +roleLabel()
    }
    class AdminOnly { middleware }
    class AppOnly { middleware }
    class AuthenticatedSessionController {
        +create() : InertiaResponse
        +store() : RedirectResponse
        +destroy() : RedirectResponse
    }
    class SocialiteController {
        +redirect() : RedirectResponse
        +callback() : RedirectResponse
    }
    User <.. AuthenticatedSessionController : memvalidasi kredensial
    User <.. SocialiteController : google_id
    AuthenticatedSessionController ..> AdminOnly : memicu
    AuthenticatedSessionController ..> AppOnly : memicu
```

## 3. Diagram Kelas — Arsitektur Controller (per paket)

```mermaid
classDiagram
    class Controller {
        <<base abstract>>
    }
    class BaseAppController {
        +tahunAjaran aktif
        +tanggal_sekarang
        +data(): array
    }
    class DashboardController {
        +__invoke() : InertiaResponse
    }

    Controller <|-- BaseAppController
    BaseAppController <|-- DashboardController

    namespace Admin {
        class DashboardController_Admin {
            +__invoke() : 10 dataset KPI
        }
        class JurusanController { +index() +store() +update() +destroy() }
        class KelasController { +index() +store() +update() +destroy() }
        class MatpelController { +index() +store() +update() +destroy() }
        class TahunAjaranController { +index() +store() +update() +destroy() }
        class SiswaController { +index() +store() +update() +destroy() }
        class AkunSiswaController { +show() +store() +update() +destroy() }
        class SiswaKelasController { +index() +store() +update() +destroy() }
        class PengajarController { +index() +store() +update() +destroy() }
        class AkunGuruController { +show() +store() +update() +destroy() }
        class GuruKelasController { +index() +store() +update() +destroy() }
        class PenilaianController { +index() +store() +show() +update() +destroy() }
        class DetailPenilaianController { +filter() +detail() +store() }
        class NaikKelasController { +index() +preview() +execute() }
        class AkunAdminController { +index() +store() +update() +destroy() }
        class ProfilController { +index() +update() }
    }

    namespace Guru {
        class DashboardGuruController { +__invoke() }
        class MateriController_Guru { +index() +store() +update() +destroy() +edit() +katalog() +salin() +unduh() }
        class TugasController_Guru { +index() +store() +update() +destroy() +edit() +nilai() +pengumpulan() +unduh() }
        class PenilaianController_Guru { +index() +rekap() +show() +store() }
    }

    namespace Siswa {
        class MateriController_Siswa { +index() +show() +lihat() +unduh() }
        class TugasController_Siswa { +index() +show() +kumpul() +unduh() }
        class PenilaianController_Siswa { +index() }
    }

    namespace Infra {
        class MataPelajaranGuruController { +index() }
        class KelasController_Room { +__invoke() }
        class LinkExternalController { +link() }
    }
```

## 4. Diagram Kelas — Services & Support

```mermaid
classDiagram
    class NaikKelasService {
        +preview(kelasSumber, kelasTujuan, tahunAjaran) : array
        +execute(kelasSumber, kelasTujuan, tahunAjaran) : int
        +validateTarget() : void
        +klasifikasiTingkat() : void
    }
    class Toast {
        +success(message)
        +error(message)
        +info(message)
        +warning(message)
    }
    class Kelas {
        +tingkatSekarang()
        +tingkatDariNama()
    }
    NaikKelasService ..> Kelas : memakai tingkat
    Controllers ..> Toast : notifikasi
    Controllers ..> NaikKelasService : proses naik kelas
```

## 5. Deskripsi Relasi Penting

| Relasi | Jenis | Penjelasan |
|--------|-------|------------|
| `User` → `Guru` / `Siswa` | 1:0..1 | Kolom `guru_id` / `nisn` pada tabel `users` menghubungkan akun login dengan data profil. |
| `Guru` → `GuruKelas` | 1:N | Satu guru dapat mengampu banyak kelas (dengan mata pelajaran tertentu) per tahun ajaran. |
| `Kelas` → `Kelas` (parent_id) | 1:N | Kelas berbasis tingkatan: **kelas induk** (mis. `XI RPL`) menjadi parent dari **rombongan** (mis. `XI RPL 1`). |
| `Siswa` → `SiswaKelas` → `Kelas` | 1:N:1 | Keanggotaan siswa pada kelas bersifat historis per tahun ajaran (`tahun_ajaran_id`). |
| `GuruKelas` → `Materi`/`Tugas` | 1:N | Materi dan tugas dibagikan ke kelas yang diampu guru (relasi `guru_kelas_id`). |
| `Tugas` → `TugasPengumpulan` | 1:N | Satu tugas dapat dikumpulkan banyak siswa; nilai tugas disimpan pada `nilai` pengumpulan. |
| `Penilaian` → `PenilaianKelas` → `Kelas` | 1:N:1 | Satu penilaian (mis. "UTS", "PAS") menjangkau banyak kelas. |
| `Penilaian` → `DetailPenilaian` | 1:N | Nilai tiap siswa per penilaian disimpan pada `detail_penilaian`, dengan referensi `guru_kelas_id` & `tahun_ajaran_id`. |
| `Tugas` → `Penilaian` | N:1 | Tugas dapat dikaitkan ke salah satu penilaian agar nilainya dapat diakumulasi. |