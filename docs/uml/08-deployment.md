# Deployment Diagram — KELAS DIGITAL IFSU

Diagram deployment menggambarkan topologi infrastruktur ketika aplikasi dijalankan di lingkungan produksi. Notasi: Mermaid `flowchart` dengan `node` untuk perangkat keras, `artifact` untuk artefak, dan `database` untuk penyimpanan.

## 1. Topologi Produksi

```mermaid
flowchart TB
    subgraph Client["Perangkat Klien"]
        B1["Browser (Chrome/Edge/Firefox)
            SPA Svelte 5
            Inertia client"]
    end

    subgraph Net["Jaringan"]
        DNS["DNS + Reverse Proxy
            Nginx / Caddy
            TLS (HTTPS)"]
        CDN["CDN
            aset statis
            (bootstrap.js, css)"]
    end

    subgraph Server["Server Aplikasi"]
        PHP["PHP-FPM 8.5"]
        subgraph Artifacts["Artefak"]
            LARAVEL["Artifact: Laravel 13
                (routes, controllers,
                 models, services)"]
            INERTIA["Artifact: Inertia.js
                server adapter"]
            VITE_MAN["Artifact: build Vite
                public/build/
                (manifest.json)"]
        end
        PHP --> LARAVEL
        LARAVEL --> INERTIA
        LARAVEL --> VITE_MAN
    end

    subgraph Storage["Penyimpanan"]
        DB[("PostgreSQL 16
            database ifsu_cbt")]
        REDIS[("Redis
            cache, session, queue")]
        FS["Volume: storage/app/public
            materi/, tugas/, tugas-kumpul/,
            profil/"]
        SCH["Artifact: database/schema
            pgsql-schema.sql"]
    end

    subgraph External["Layanan Eksternal"]
        GOOGLE["Google OAuth 2.0
            identity provider"]
        MAIL["Mail server
            (verifikasi email)"]
    end

    B1 <-->|HTTPS/HTTP| DNS
    DNS --> PHP
    B1 <-->|aset statis| CDN
    LARAVEL --> DB
    LARAVEL --> REDIS
    LARAVEL --> FS
    DB --> SCH
    LARAVEL --> GOOGLE
    LARAVEL --> MAIL
```

## 2. Topologi Pengembangan (Local / Dev)

```mermaid
flowchart LR
    subgraph Dev["Mesin Pengembang (Windows)"]
        VSC["IDE (VS Code / PHPStorm)"]
        ARTISAN["php artisan serve
            atau valet/herd"]
        VDEV["Vite Dev Server
            bun run dev
            (HMR + SSR)"]
        PGT["PostgreSQL 16 lokal
            (D:\\dev\\pgsql)"]
        PHPCLI["PHP 8.5 CLI + Composer"]
    end

    VSC --> PHPCLI
    PHPCLI --> ARTISAN
    PHPCLI --> VDEV
    ARTISAN --> PGT
```

## 3. Spesifikasi Node

| Node | Tipe | Spesifikasi / Keterangan |
|------|------|--------------------------|
| Browser | Perangkat klien | Browser modern mendukung ES2020+ |
| Reverse Proxy | Jaringan | Nginx/Caddy; TLS; proxy `/` ke PHP-FPM; `public/` sebagai docroot |
| PHP-FPM | Server | PHP 8.5, ekstensi `pdo_pgsql`, `fileinfo`, `mbstring` |
| Laravel | Artefak | Kode sumber + vendor (`composer install --no-dev`) |
| Build Vite | Artefak | Hasil `bun run build`; direferensikan lewat manifest |
| PostgreSQL | Basis data | v16; database `ifsu_cbt`; user app dengan hak CRUD |
| Redis | Penyimpanan | Cache session & rate limiter |
| Storage | Volume | `storage/app/public` di-link ke `public/storage` |
| Google OAuth | Eksternal | Client ID/Secret di `.env` (`services.google`) |

## 4. Alur Rilis (Pipeline)

```mermaid
flowchart LR
    S1[git commit + push] --> S2[CI: composer install]
    S2 --> S3[CI: bun install + bun run build]
    S3 --> S4[CI: php artisan test]
    S4 --> S5[CI: pint + larastan]
    S5 --> S6{Sukses?}
    S6 -->|Ya| D1[Deploy: rsync / git pull]
    S6 -->|Tidak| F1[Fix & commit ulang]
    F1 --> S1
    D1 --> D2[php artisan migrate --force]
    D2 --> D3[php artisan storage:link]
    D3 --> D4[php artisan config:cache + route:cache]
    D4 --> E([Produksi siap])
```

## 5. Variabel Lingkungan Penting (`.env`)

| Variabel | Keterangan |
|----------|------------|
| `APP_URL` | URL aplikasi (dipakai URL generator & OAuth) |
| `DB_CONNECTION=pgsql` | Koneksi basis data |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Kredensial PostgreSQL |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | OAuth Google (Socialite) |
| `SESSION_DRIVER`, `CACHE_STORE` | Driver session/cache |
| `FILESYSTEM_DISK=public` | Disk penyimpanan file unggahan |