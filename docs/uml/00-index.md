# Dokumentasi UML — KELAS DIGITAL IFSU

Dokumen ini berisi seluruh diagram UML standar untuk aplikasi **KELAS DIGITAL IFSU** (Sistem Manajemen Pembelajaran berbasis web). Seluruh diagram ditulis dalam **notasi Mermaid** sehingga dapat dirender langsung di GitHub, VS Code (plugin Markdown Preview Mermaid), atau mermaid.live.

## Daftar Dokumen

| No | Dokumen | Isi | Standar |
|----|---------|-----|---------|
| 1 | `01-use-case.md` | Diagram use case per aktor (Admin, Guru, Siswa, Tamu) | UML Use Case |
| 2 | `02-class.md` | Diagram kelas seluruh model + relasi antar entitas | UML Class Diagram |
| 3 | `03-sequence.md` | Diagram urutan untuk alur utama (login, materi, tugas, penilaian, naik kelas) | UML Sequence Diagram |
| 4 | `04-activity.md` | Diagram aktivitas untuk proses bisnis utama | UML Activity Diagram |
| 5 | `05-state.md` | Diagram state untuk objek-objek berstatus | UML State Machine |
| 6 | `06-erd.md` | Entity Relationship Diagram seluruh basis data | ERD / Mermaid erDiagram |
| 7 | `07-component.md` | Diagram komponen arsitektur aplikasi (Laravel + Inertia + Svelte) | UML Component Diagram |
| 8 | `08-deployment.md` | Diagram deployment infrastruktur produksi | UML Deployment Diagram |

## Profil Singkat Aplikasi

| Atribut | Nilai |
|---------|-------|
| Nama Aplikasi | KELAS DIGITAL IFSU |
| Jenis | Sistem Manajemen Pembelajaran (LMS) berbasis web |
| Arsitektur | Monolitik dengan pemisahan frontend (SPA) dan backend (API web) |
| Backend | Laravel 13 (PHP 8.5) |
| Frontend | Inertia.js v3 + Svelte 5 + Tailwind CSS + Vite |
| Basis Data | PostgreSQL 16 |
| Autentikasi | Email + password, OAuth Google (Socialite) |
| Pengujian | Pest (unit & feature), PHPStan/Larastan |
| Pemformatan | Laravel Pint |
| Nama paket BPMN | bpmn-js (lihat `docs/bpmn/`) |

## Aktor Utama

1. **Tamu** (belum login) — hanya dapat mengakses halaman login dan OAuth Google.
2. **Admin** — mengelola seluruh data master, akun, penilaian, dan proses kenaikan kelas.
3. **Guru** (pengajar) — mengelola materi, tugas, dan input penilaian untuk kelas yang diampu.
4. **Siswa** — melihat materi, mengumpulkan tugas, dan melihat nilai.

## Struktur Direktori Aplikasi

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/            # 14 controller sisi admin
│   │   ├── Guru/             # 4 controller sisi guru
│   │   ├── Siswa/            # 3 controller sisi siswa
│   │   └── Auth/…            # login, logout, OAuth Google
│   ├── Middleware/           # AdminOnly, AppOnly, BaseController
│   └── Requests/
├── Models/                   # 14 model Eloquent
├── Services/                 # NaikKelasService, dll.
└── Support/
resources/
├── js/
│   ├── pages/                # 33 halaman Svelte (admin/guru/siswa/auth)
│   ├── components/           # komponen UI bersama
│   └── layouts/              # layout per peran
routes/web.php                # 97 route terdaftar
database/
├── migrations/               # 57 file migrasi
├── seeders/                  # seeder data master + mock
└── schema/                   # dump schema PostgreSQL
```

## Notasi & Konvensi

- Semua diagram Mermaid dapat dirender otomatis oleh GitHub/GitLab/VS Code.
- Label kelas memakai nama kelas PHP aktual; atribut & metode memakai nama asli pada kode.
- Relasi memakai kardinalitas sesuai foreign key pada skema basis data.