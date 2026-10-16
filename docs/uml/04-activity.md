# Activity Diagram — KELAS DIGITAL IFSU

Diagram aktivitas memodelkan alur kerja (workflow) proses bisnis utama aplikasi. Notasi: Mermaid `flowchart` dengan simbol keputusan (diamond) dan swimlane per aktor.

## 1. Alur Login

```mermaid
flowchart TB
    Start([Mulai]) --> Open{Metode login?}
    Open -->|Email & Password| Form[Isi email & password]
    Form --> Submit[POST /login]
    Submit --> Rate{Melebihi 5 percobaan / menit?}
    Rate -->|Ya| Block[Tampilkan error 429]
    Block --> End([Selesai])
    Rate -->|Tidak| Auth{Validasi kredensial}
    Auth -->|Salah| Err[Tampilkan error validasi]
    Err --> Form
    Auth -->|Benar| Reg[Regenerasi session]
    Open -->|Google OAuth| GRed[Redirect ke Google]
    GRed --> GCall[Callback /auth/google/callback]
    GCall --> GOk{Email terdaftar?}
    GOk -->|Tidak| GErr[Tampilkan toast error]
    GErr --> End
    GOk -->|Ya| Reg
    Reg --> Role{Peran user?}
    Role -->|Admin| DAdmin[Redirect admin dashboard]
    Role -->|Guru| DGuru[Redirect dashboard guru]
    Role -->|Siswa| DSiswa[Redirect dashboard siswa]
    DAdmin --> End
    DGuru --> End
    DSiswa --> End
```

## 2. Alur Naik Kelas (Admin)

```mermaid
flowchart TB
    Start([Mulai]) --> Open[Admin buka halaman Naik Kelas]
    Open --> Pick[Pilih kelas sumber, kelas tujuan, tahun ajaran]
    Pick --> Preview[Klik Preview]
    Preview --> Valid{Validasi aturan}
    Valid -->|Kelas tujuan tidak valid| VErr[Tampilkan error toast]
    VErr --> Pick
    Valid -->|Valid| List[NaikKelasService ambil siswa aktif kelas sumber]
    List --> Show[Tampilkan pratinjau siswa yang dipindah]
    Show --> Confirm{Admin konfirmasi?}
    Confirm -->|Tidak| Cancel[Batalkan]
    Cancel --> End([Selesai])
    Confirm -->|Ya| Execute[Klik Execute]
    Execute --> Txn{Transaksi DB}
    Txn -->|Gagal| TErr[Rollback + toast error]
    TErr --> End
    Txn -->|Sukses| Deact[NONAKTIFKAN SiswaKelas lama]
    Deact --> New[Buat SiswaKelas baru di kelas tujuan]
    New --> Notif[Toast success + jumlah siswa]
    Notif --> Redirect[Redirect PRG ke index]
    Redirect --> End
```

## 3. Alur Kelola Tugas (Guru → Siswa → Guru)

```mermaid
flowchart LR
    subgraph Guru
        G1([Mulai]) --> G2[Buat tugas]
        G2 --> G3[Isi judul, deskripsi, deadline, jenis, poin, kelas, file]
        G3 --> G4{Validasi?}
        G4 -->|Gagal| G3
        G4 -->|Ok| G5[Tugas::create]
        G5 --> G6[Toast + redirect]
        G6 --> G7{Tugas dikumpulkan siswa?}
        G7 -->|Ya| G8[Buka daftar pengumpulan]
        G8 --> G9[Nilai per pengumpulan]
        G9 --> G10[Toast + redirect]
        G10 --> G11([Selesai guru])
    end

    subgraph Siswa
        S1([Mulai]) --> S2[Lihat daftar tugas]
        S2 --> S3[Klik detail tugas]
        S3 --> S4{Kumpulkan?}
        S4 -->|Tidak| S5([Selesai siswa])
        S4 -->|Ya| S6[Unggah file / jawaban teks]
        S6 --> S7{Validasi file?}
        S7 -->|Gagal| S6
        S7 -->|Ok| S8[create/update TugasPengumpulan]
        S8 --> S9[Toast + redirect]
        S9 --> S10([Selesai siswa])
    end

    G5 -.->|terbit| S2
    S8 -.->|masuk daftar| G8
```

## 4. Alur Input Penilaian (Guru)

```mermaid
flowchart TB
    Start([Mulai]) --> L[Guru buka Penilaian]
    L --> P{Pilih penilaian + kelas?}
    P -->|Tidak| End([Selesai])
    P -->|Ya| D[GET penilaian/{penilaian}/{guruKelas}]
    D --> R[Ambil siswa kelas + nilai eksisting]
    R --> F[Isi nilai semua siswa]
    F --> V{Valid? <= nilai_maks}
    V -->|Tidak| E1[Tampilkan error]
    E1 --> F
    V -->|Ya| S[bulk updateOrCreate DetailPenilaian]
    S --> T[Toast success]
    T --> Rk{Buka rekap?}
    Rk -->|Ya| X[GET rekap → agregasi per kelas]
    X --> End
    Rk -->|Tidak| End
```

## 5. Alur Kelola Materi (Guru)

```mermaid
flowchart TB
    Start([Mulai]) --> Menu{Tindakan guru}
    Menu -->|Buat materi| F1[Form judul, deskripsi, konten, kelas, file]
    F1 --> V1{Valid?}
    V1 -->|Tidak| F1
    V1 -->|Ya| C1[Materi::create]
    C1 --> N1[Toast + redirect]
    Menu -->|Ubah| F2[Form edit]
    F2 --> V2{Valid?}
    V2 -->|Tidak| F2
    V2 -->|Ya| U2[Materi::update]
    U2 --> N2[Toast + redirect]
    Menu -->|Hapus| D3[Hapus record + file]
    D3 --> N3[Toast + redirect]
    Menu -->|Katalog| K1[Lihat materi semua guru]
    K1 --> K2{Salin ke kelas sendiri?}
    K2 -->|Ya| K3[Materi::salin]
    K3 --> N4[Toast + redirect]
    K2 -->|Tidak| N5([Selesai])
    N1 --> N5
    N2 --> N5
    N3 --> N5
    N4 --> N5
```

## 6. Alur Kelola Akun (Admin)

```mermaid
flowchart TB
    Start([Mulai]) --> Tgt{Tujuan admin}
    Tgt -->|Akun guru| G1[Buka admin/pengajar/{guru}/akun]
    G1 --> G2[Isi email & password]
    G2 --> G3[firstOrCreate user + set guru_id & role]
    G3 --> GN[Toast + redirect]
    Tgt -->|Akun siswa| S1[Buka admin/siswa/{siswa}/akun]
    S1 --> S2[Isi email & password]
    S2 --> S3[firstOrCreate user + set nisn & role]
    S3 --> SN[Toast + redirect]
    Tgt -->|Akun admin| A1[Buka admin/akun-admin]
    A1 --> A2[Isi nama, email, password]
    A2 --> A3[User::create role admin]
    A3 --> AN[Toast + redirect]
    GN --> End([Selesai])
    SN --> End
    AN --> End
```

## 7. Alur Profil (Semua Peran)

```mermaid
flowchart TB
    Start([Mulai]) --> P[PUT /profil]
    P --> V{Validasi data?}
    V -->|Gagal| E[Tampilkan error]
    E --> P
    V -->|Ok| U[Update profil + foto opsional]
    U --> T[Toast success]
    T --> End([Selesai])
```

## 8. Alur Dashboard (Semua Peran)

```mermaid
flowchart TB
    Start([Mulai]) --> D{Peran?}
    D -->|Admin| DA[DashboardController admin]
    DA --> K1[10 dataset: jumlah jurusan, kelas, matpel, siswa, guru, penilaian, tahun ajaran aktif, dst.]
    K1 --> E1([Render admin/Index.svelte])
    D -->|Guru| DG[Dashboard guru]
    DG --> K2[Jumlah materi, tugas, kelas diampu, pengumpulan perlu dinilai]
    K2 --> E2([Render Dashboard.svelte])
    D -->|Siswa| DS[Dashboard siswa]
    DS --> K3[Jumlah materi, tugas, nilai]
    K3 --> E3([Render Dashboard.svelte])
    E1 --> End([Selesai])
    E2 --> End
    E3 --> End
```