# Component Diagram — KELAS DIGITAL IFSU

Diagram komponen menggambarkan struktur arsitektur aplikasi dan dependensi antar komponen perancah. Notasi: Mermaid `flowchart`.

## 1. Arsitektur Keseluruhan (3-Layer)

```mermaid
flowchart TB
    subgraph Client["Lapisan Klien (Browser)"]
        SPA["SPA Inertia + Svelte 5
            resources/js/pages
            33 halaman"]
        UI["Komponen UI
            Tailwind CSS + shadcn-svelte
            Toast (sonner)"]
        SPA --> UI
    end

    subgraph HTTP["HTTP / Protokol"]
        GET["GET / POST / PUT / DELETE
            (Inertia request, XHR)"]
        FILE["File upload/download
            materi/, tugas/, tugas-kumpul/
            (public disk)"]
    end

    subgraph Server["Lapisan Server (Laravel)"]
        RT["Router
            routes/web.php
            97 route"]
        MID["Middleware
            auth, AdminOnly, AppOnly,
            ThrottleRequests"]
        CTL["Controllers
            32 controller
            (Admin 14, Guru 4, Siswa 3,
             auth & infra)"]
        VAL["Validasi
            FormRequest + inline
            Validator"]
        SRV["Services
            NaikKelasService
            Toast notification"]
        MDL["Models (Eloquent)
            14 model"]
        RT --> MID
        MID --> CTL
        CTL --> VAL
        CTL --> SRV
        CTL --> MDL
        SRV --> MDL
    end

    subgraph Data["Lapisan Data"]
        DB[("PostgreSQL 16")]
        FS["Storage public/
            materi, tugas, tugas-kumpul,
            profil (foto)"]
        CACHE[("Cache & Session")]
    end

    subgraph External["Eksternal"]
        GOOGLE["Google OAuth 2.0
            (Socialite)"]
    end

    Client <--> HTTP
    HTTP <--> Server
    MDL --> DB
    SRV --> DB
    CTL --> FS
    MDL <--> CACHE
    MID <--> CACHE
    CTL --> GOOGLE
```

## 2. Diagram Komponen — Frontend (Inertia + Svelte)

```mermaid
flowchart TB
    subgraph Frontend["Frontend (Vite + Svelte 5)"]
        ENTRY["resources/js/app.ts
            (createInertiaApp)"]
        PLUGIN["@inertiajs/vite plugin
            SSR dev mode"]
        LAYOUT["layouts/
            AppLayout (menu per peran)"]
        PAGES["pages/
            auth/Login, Dashboard,
            admin/* (17 halaman),
            guru/* (10 halaman),
            siswa/* (5 halaman)"]
        COMP["components/
            DataTable, FormModal,
            UploadDropzone, dll."]
        TYPES["@/types
            tipe Inertia PageProps"]
        WAYFINDER["@/actions & @/routes
            (Laravel Wayfinder)"]

        ENTRY --> LAYOUT
        ENTRY --> PAGES
        PAGES --> COMP
        PAGES --> WAYFINDER
        PAGES --> TYPES
        LAYOUT --> WAYFINDER
    end

    subgraph Backend["Backend (Laravel)"]
        INERTIA["Inertia::render()
            shared props (auth, tahunAjaran, tanggal)"]
    end

    Frontend <-->|"Inertia protocol
        (page visits, form helper)"| Backend
```

## 3. Diagram Komponen — Sisi Admin

```mermaid
flowchart LR
    subgraph Admin["Modul Admin (prefix /admin)"]
        DASH[Admin\\DashboardController]
        MASTER[Master Data Controllers
            Jurusan, Kelas, Matpel, TahunAjaran]
        DATA[Data Controllers
            Siswa, Pengajar]
        AKUN[Akun Controllers
            AkunGuru, AkunSiswa, AkunAdmin]
        ATUR[Penempatan
            SiswaKelas, GuruKelas]
        NILAI[Penilaian & DetailPenilaian]
        NAIK[NaikKelasController]
        PROFIL[ProfilController]
    end

    subgraph Services["Services"]
        NK[NaikKelasService]
        TOAST[Toast]
    end

    DASH --> MASTER
    DASH --> DATA
    MASTER --> TOAST
    DATA --> AKUN
    AKUN --> TOAST
    DATA --> ATUR
    ATUR --> TOAST
    NILAI --> TOAST
    NAIK --> NK
    NAIK --> TOAST
    PROFIL --> TOAST
```

## 4. Diagram Komponen — Modul Guru & Siswa

```mermaid
flowchart LR
    subgraph Guru["Modul Guru (prefix /app/guru)"]
        GM[Guru\\MateriController]
        GT[Guru\\TugasController]
        GP[Guru\\PenilaianController]
        GD[Guru\\DashboardGuruController]
    end

    subgraph Siswa["Modul Siswa (prefix /app)"]
        SM[Siswa\\MateriController]
        ST[Siswa\\TugasController]
        SP[Siswa\\PenilaianController]
    end

    subgraph Infra["Modul Bersama"]
        MP[MataPelajaranGuruController]
        KC[KelasController (room)]
        LE[LinkExternalController]
    end

    subgraph Files["File Storage (public disk)"]
        DM["materi/ (upload & unduh)"]
        DT["tugas/ (upload & unduh)"]
        DK["tugas-kumpul/ (kumpul & unduh)"]
    end

    GM --> DM
    GT --> DT
    ST --> DK
    GT -.->|nilai| ST
    GP --> SP
```

## 5. Diagram Komponen — Autentikasi

```mermaid
flowchart LR
    subgraph Auth["Modul Auth"]
        ASC[AuthenticatedSessionController
            create / store / destroy]
        SOC[SocialiteController
            redirect / callback]
        ADMIN_ONLY[Middleware AdminOnly]
        APP_ONLY[Middleware AppOnly]
    end

    subgraph Config["Konfigurasi"]
        RATE["ThrottleRequests
            5 percobaan/menit per email+IP"]
        SOCIAL["config/services
            google client_id/secret"]
    end

    ASC --> RATE
    SOC --> SOCIAL
    ADMIN_ONLY --> ASC
    APP_ONLY --> ASC
```

## 6. Tabel Komponen (Peran & Artifact)

| Komponen | Artifact | Teknologi |
|----------|----------|-----------|
| Router | `routes/web.php` | Laravel Router |
| Middleware | `AdminOnly`, `AppOnly`, `auth`, `throttle` | Laravel Middleware |
| Controller Admin | `app/Http/Controllers/Admin/*` (14) | PHP |
| Controller Guru | `app/Http/Controllers/Guru/*` (4) | PHP |
| Controller Siswa | `app/Http/Controllers/Siswa/*` (3) | PHP |
| Service | `NaikKelasService`, `Toast` | PHP |
| Model | `app/Models/*` (14) | Eloquent ORM |
| Halaman Admin | `resources/js/pages/admin/*` (17) | Svelte 5 |
| Halaman Guru | `resources/js/pages/guru/*` (10) | Svelte 5 |
| Halaman Siswa | `resources/js/pages/siswa/*` (5) | Svelte 5 |
| Layout | `resources/js/layouts/*` | Svelte 5 |
| Typed Routes | `resources/js/routes/*` (Wayfinder) | TypeScript |
| Migrasi | `database/migrations/*` (57) | Laravel Migrations |
| Seeder | `database/seeders/*` (8) | PHP |
| Skema DB | `database/schema/pgsql-schema.sql` | PostgreSQL dump |