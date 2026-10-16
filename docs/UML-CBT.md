# UML & BPMN — Modul CBT (Computer Based Test)

Dokumentasi perancangan **Modul CBT** aplikasi CBT SMK Informatika Sumedang, mengikuti standar **OMG UML 2.5.1** dan **OMG BPMN 2.0**. Seluruh diagram ditulis dalam **notasi Mermaid** sehingga dapat dirender langsung di GitHub, VS Code, atau mermaid.live.

> Ruang lingkup: **hanya Modul CBT** (ujian, soal, bank soal, pengerjaan, penilaian, proctoring). Dokumentasi modul lain (materi, tugas, naik kelas) ada di `docs/uml/`.

## Daftar Isi

| No | Bagian | Standar |
|----|--------|---------|
| 1 | Diagram Use Case | UML 2.5.1 — Use Case Diagram |
| 2 | Diagram Kelas | UML 2.5.1 — Class Diagram |
| 3 | Diagram Sekuens (4 alur) | UML 2.5.1 — Sequence Diagram |
| 4 | Diagram Aktivitas (2 alur) | UML 2.5.1 — Activity Diagram |
| 5 | Diagram State Machine (2 objek) | UML 2.5.1 — State Machine Diagram |
| 6 | BPMN 2.0 (3 proses bisnis) | OMG BPMN 2.0 |

### Konvensi

- Nama kelas/metode/atribut memakai nama aktual pada kode (`app/Models/`, `app/Services/`).
- Stereotip memakai notasi OMG: `<<entity>>`, `<<service>>`, `<<controller>>`, `<<boundary>>`.
- Visibilitas: `+` public, `-` private, `#` protected.

---

## 1. Diagram Use Case

Aktor: **Admin**, **Guru**, **Siswa**, dan **Sistem** (otomasi: penilaian objektif, auto-submit, sinkronisasi nilai).

```mermaid
flowchart LR
    subgraph Aktor
        A[Admin]
        G[Guru]
        S[Siswa]
        SYS[Sistem]
    end

    subgraph "Modul CBT"
        UC1[Kelola Periode Ujian]
        UC2[Kelola Bank Soal]
        UC3[Buat & Kelola Ujian]
        UC4[Kelola Soal Ujian]
        UC5[Ambil Soal dari Bank Soal]
        UC6[Generate Soal Acak dari Bank]
        UC7[Terbitkan / Tarik Ujian]
        UC8[Generate & Rilis Token Ujian]
        UC9[Lihat Hasil & Rekap Nilai]
        UC10[Koreksi Soal Esai]
        UC11[Lihat Daftar Ujian]
        UC12[Mulai Ujian dengan Token]
        UC13[Kerjakan Ujian]
        UC14[Lihat Hasil Ujian Sendiri]
        UC15[Nilai Otomatis Soal Objektif]
        UC16[Auto-Submit Waktu Habis]
        UC17[Catat Pelanggaran Proctoring]
        UC18[Diskualifikasi Otomatis]
        UC19[Sinkronisasi Nilai ke Penilaian]
    end

    A --> UC1
    A --> UC3
    A --> UC7
    A --> UC8
    A --> UC9
    A --> UC10
    A --> UC2
    G --> UC2
    G --> UC3
    G --> UC4
    G --> UC5
    G --> UC6
    G --> UC7
    G --> UC8
    G --> UC9
    G --> UC10
    S --> UC11
    S --> UC12
    S --> UC13
    S --> UC14
    UC12 -.->|include| UC8
    UC13 -.->|include| UC17
    SYS --> UC15
    SYS --> UC16
    SYS --> UC17
    SYS --> UC18
    SYS --> UC19
    UC18 -.->|extend| UC17
    UC15 -.->|include| UC19
    UC10 -.->|include| UC19
```

### Deskripsi Use Case

| ID | Use Case | Aktor | Deskripsi |
|----|----------|-------|-----------|
| UC1 | Kelola Periode Ujian | Admin | CRUD periode (UTS/UAS/USBK) per tahun ajaran; dipakai validasi jadwal ujian besar |
| UC2 | Kelola Bank Soal | Admin, Guru | CRUD BankSoal + BankOpsiSoal per mata pelajaran (topik, tingkat kesulitan) |
| UC3 | Buat & Kelola Ujian | Admin, Guru | Buat ujian (kategori, jadwal, durasi, aturan acak/proctoring); hapus diblokir bila sudah dikerjakan |
| UC4 | Kelola Soal Ujian | Guru | CRUD Soal + OpsiSoal per ujian; ubah/hapus diblokir bila ujian sudah dikerjakan |
| UC5 | Ambil Soal dari Bank Soal | Guru | Salin snapshot soal terpilih dari bank ke ujian |
| UC6 | Generate Soal Acak dari Bank | Guru | Ambil N soal acak by kriteria (tipe/topik/kesulitan) lalu salin ke ujian |
| UC7 | Terbitkan / Tarik Ujian | Admin, Guru | Ubah status draft ↔ terbit (syarat: minimal 1 soal) |
| UC8 | Generate & Rilis Token Ujian | Admin, Guru | Buat token 6 karakter; rilis/tahan gerbang pengerjaan |
| UC9 | Lihat Hasil & Rekap Nilai | Admin, Guru | Rekap per siswa + detail koreksi per pengerjaan |
| UC10 | Koreksi Soal Esai | Admin, Guru | Beri skor esai (0–poin); picu hitung ulang nilai |
| UC11 | Lihat Daftar Ujian | Siswa | Daftar ujian terbit untuk kelasnya + status pengerjaan |
| UC12 | Mulai Ujian dengan Token | Siswa | Validasi token, jendela jadwal, sisa attempt → buat sesi |
| UC13 | Kerjakan Ujian | Siswa | Jawab soal (acak deterministik), timer, simpan jawaban per soal |
| UC14 | Lihat Hasil Ujian Sendiri | Siswa | Nilai hanya bila `tampilkan_hasil=true` (nilai tidak dikirim ke browser bila false) |
| UC15 | Nilai Otomatis Soal Objektif | Sistem | Nilai pg/multi/benar_salah/isian saat finalisasi |
| UC16 | Auto-Submit Waktu Habis | Sistem | Finalisasi status `auto_submit` saat `batas_at` tercapai |
| UC17 | Catat Pelanggaran Proctoring | Sistem | blur, exit_fullscreen, tanpa_fullscreen → increment counter |
| UC18 | Diskualifikasi Otomatis | Sistem | Finalisasi status `diskualifikasi` bila pelanggaran ≥ ambang |
| UC19 | Sinkronisasi Nilai ke Penilaian | Sistem | Tulis `nilai_total` ke DetailPenilaian bila semua esai terkoreksi |

---

## 2. Diagram Kelas

```mermaid
classDiagram
    class Ujian {
        <<entity>>
        +int id
        +int guru_id
        +int guru_kelas_id
        +int? penilaian_id
        +string kategori
        +string judul
        +string? deskripsi
        +datetime? tanggal_mulai
        +datetime? tanggal_selesai
        +int durasi_menit
        +int maks_attempt
        +bool acak_soal
        +bool acak_opsi
        +bool tampilkan_hasil
        +bool wajib_fullscreen
        +int maks_pelanggaran
        +string? token
        +datetime? token_released_at
        +string? token_pengelola
        +int nilai_maks
        +int bobot
        +string status
        +sudahTerbit() bool
        +sudahMulai() bool
        +sudahSelesai() bool
        +sedangBerlangsung() bool
        +totalPoin() int
        +tokenValid(token) bool
        +kategoriBesar() bool
    }

    class Soal {
        <<entity>>
        +int id
        +int ujian_id
        +int? bank_soal_id
        +string tipe
        +string pertanyaan
        +int poin
        +int urutan
        +array? kunci_isian
        +bool isian_case_sensitive
        +objektif() bool
        +butuhOpsi() bool
    }

    class OpsiSoal {
        <<entity>>
        +int id
        +int soal_id
        +string teks
        +bool benar
        +int urutan
    }

    class BankSoal {
        <<entity>>
        +int id
        +int matpel_id
        +int? guru_id
        +string tipe
        +string pertanyaan
        +int poin
        +string? topik
        +string kesulitan
        +array? kunci_isian
        +bool isian_case_sensitive
        +butuhOpsi() bool
        +scopeUntukMatpel(matpelIds) Builder
    }

    class BankOpsiSoal {
        <<entity>>
        +int id
        +int bank_soal_id
        +string teks
        +bool benar
        +int urutan
    }

    class UjianPengerjaan {
        <<entity>>
        +int id
        +int ujian_id
        +string siswa_nisn
        +int attempt_ke
        +datetime mulai_at
        +datetime batas_at
        +datetime? submitted_at
        +string status
        +float? nilai_objektif
        +float? nilai_esai
        +float? nilai_total
        +int jumlah_pelanggaran
        +sedangBerlangsung() bool
        +waktuHabis() bool
        +sisaDetik() int
    }

    class JawabanSiswa {
        <<entity>>
        +int id
        +int ujian_pengerjaan_id
        +int soal_id
        +array? opsi_dipilih
        +string? jawaban_teks
        +bool? benar
        +float? skor
    }

    class PelanggaranLog {
        <<entity>>
        +int id
        +int ujian_pengerjaan_id
        +string jenis
        +datetime terjadi_at
    }

    class PeriodeUjian {
        <<entity>>
        +int id
        +string kategori
        +int tahun_ajaran_id
        +string nama
        +datetime tanggal_mulai
        +datetime? tanggal_selesai
        +sedangAktif() bool
        +sudahLewat() bool
        +akanDatang() bool
    }

    class UjianService {
        <<service>>
        +generateToken() string
        +nilaiJawabanObjektif(soal, jawaban) bool
        +gradeObjektif(pengerjaan) void
        +rekalkulasiNilai(pengerjaan) bool
        +syncPenilaian(pengerjaan) void
        +defaultKategori(kategori) array
    }

    class BankSoalService {
        <<service>>
        +salinKeUjian(ujian, bankSoalIds) int
        +simpanDariSoal(soal, matpelId, guruId, meta) BankSoal
        +generate(ujian, kriteria) int
    }

    Ujian "1" *-- "0..*" Soal : berisi >
    Soal "1" *-- "0..*" OpsiSoal : memiliki >
    BankSoal "1" *-- "0..*" BankOpsiSoal : memiliki >
    Ujian "1" *-- "0..*" UjianPengerjaan : dikerjakan >
    UjianPengerjaan "1" *-- "0..*" JawabanSiswa : menjawab >
    UjianPengerjaan "1" *-- "0..*" PelanggaranLog : mencatat >
    Soal "0..*" --> "0..1" BankSoal : snapshot dari >
    JawabanSiswa "0..*" --> "1" Soal : merujuk >
    PeriodeUjian "0..*" --> "1" TahunAjaran : periode dari >
    UjianService ..> Ujian : memakai >
    UjianService ..> UjianPengerjaan : menghitung >
    BankSoalService ..> BankSoal : menyalin >
    BankSoalService ..> Soal : membuat >
```

### Catatan Relasi (notasi OMG)

| Relasi | Jenis | Multiplicity | Keterangan |
|--------|-------|--------------|------------|
| Ujian → Soal | Komposisi | 1 → 0..* | Soal hidup-mati bersama ujian |
| Soal → OpsiSoal | Komposisi | 1 → 0..* | Opsi hidup-mati bersama soal |
| BankSoal → BankOpsiSoal | Komposisi | 1 → 0..* | Opsi bank hidup-mati bersama bank soal |
| Ujian → UjianPengerjaan | Komposisi | 1 → 0..* | Sesi milik ujian |
| UjianPengerjaan → JawabanSiswa | Komposisi | 1 → 0..* | Jawaban milik sesi |
| UjianPengerjaan → PelanggaranLog | Komposisi | 1 → 0..* | Log milik sesi |
| Soal → BankSoal | Asosiasi (snapshot) | 0..* → 0..1 | `bank_soal_id` nullable; salinan, bukan referensi hidup |
| JawabanSiswa → Soal | Asosiasi | 0..* → 1 | Jawaban merujuk soal yang dijawab |
| PeriodeUjian → TahunAjaran | Asosiasi | 0..* → 1 | Periode milik satu tahun ajaran |

**Nilai enumerasi penting:**
- `Ujian.status`: `draft`, `terbit`
- `Ujian.kategori`: `kuis`, `uts`, `uas`, `usbk`
- `Soal.tipe` / `BankSoal.tipe`: `pg`, `multi`, `benar_salah`, `isian`, `esai`
- `UjianPengerjaan.status`: `berlangsung`, `selesai`, `auto_submit`, `diskualifikasi`
- `PelanggaranLog.jenis`: `blur`, `exit_fullscreen`, `tanpa_fullscreen`

---
## 3. Diagram Sekuens

### 3.1 Siswa Mulai & Kerjakan Ujian

```mermaid
sequenceDiagram
    actor S as Siswa
    participant FE as Halaman Kerjakan<br/>(Svelte)
    participant C as Siswa/UjianController
    participant Svc as UjianService
    participant DB as Database

    S->>C: POST mulai(ujian) + token
    C->>C: pastikanBolehAkses(ujian, nisn)
    alt ujian tidak sedangBerlangsung()
        C-->>S: Toast error "belum dibuka / sudah berakhir"
    else token tidak valid
        C-->>S: Toast error "token salah / belum dirilis"
    else attempt habis
        C-->>S: Toast error "kesempatan habis"
    else valid
        C->>DB: create UjianPengerjaan<br/>(status=berlangsung, batas_at)
        C-->>S: redirect ke halaman kerjakan
    end

    S->>C: GET kerjakan(ujian)
    C->>C: sesiBerjalan() → cek waktuHabis()
    C->>DB: load soal + opsi (acak deterministik)
    C-->>FE: Inertia props (soal, sisa_detik, pelanggaran)

    loop setiap jawaban
        S->>FE: pilih opsi / ketik jawaban
        FE->>C: POST simpanJawaban(soal_id, opsi_dipilih, jawaban_teks)
        C->>DB: updateOrCreate JawabanSiswa
        C-->>FE: 302 back (preserve state)
    end

    opt proctoring: tab blur / keluar fullscreen
        FE->>C: POST lapor(jenis)
        C->>DB: create PelanggaranLog + increment jumlah_pelanggaran
    end

    S->>FE: klik "Kumpulkan"
    FE->>C: POST submit(ujian)
    C->>C: finalisasi(pengerjaan, 'selesai')
    C->>Svc: gradeObjektif(pengerjaan)
    Svc->>DB: nilai tiap jawaban objektif (benar/skor)
    C->>Svc: rekalkulasiNilai(pengerjaan)
    alt semua esai sudah dikoreksi
        Svc->>Svc: syncPenilaian() → DetailPenilaian
    end
    C-->>S: redirect ke hasil
```

### 3.2 Auto-Submit: Waktu Habis & Pelanggaran Melebihi Ambang

```mermaid
sequenceDiagram
    actor S as Siswa
    participant FE as Halaman Kerjakan<br/>(Svelte + timer)
    participant C as Siswa/UjianController
    participant Svc as UjianService
    participant DB as Database

    alt skenario A — waktu habis
        FE->>FE: timer sisaDetik mencapai 0
        FE->>C: POST submit(ujian) [auto=true]
        C->>C: finalisasi(pengerjaan, 'auto_submit')
    else skenario B — pelanggaran melebihi ambang
        FE->>C: POST lapor(jenis=blur)
        C->>DB: create PelanggaranLog
        C->>DB: increment jumlah_pelanggaran
        alt jumlah_pelanggaran >= maks_pelanggaran
            C->>C: finalisasi(pengerjaan, 'diskualifikasi')
            C-->>S: Toast error "melewati batas pelanggaran"
        end
    end

    C->>Svc: gradeObjektif(pengerjaan)
    loop tiap JawabanSiswa bertipe objektif
        Svc->>Svc: nilaiJawabanObjektif(soal, jawaban)
        Svc->>DB: update benar, skor
    end
    C->>Svc: rekalkulasiNilai(pengerjaan)
    Note over Svc: esai tanpa jawaban = 0 (bukan pending);<br/>esai dijawab tapi belum dikoreksi = pending
    alt nilai_total lengkap
        Svc->>DB: updateOrCreate DetailPenilaian
    else masih ada esai pending
        Svc->>DB: nilai_total = null (menunggu koreksi)
    end
    C->>DB: update UjianPengerjaan<br/>(status, submitted_at, nilai_*)
```

### 3.3 Guru Koreksi Esai & Sinkronisasi Nilai

```mermaid
sequenceDiagram
    actor G as Guru
    participant C as Guru/HasilUjianController
    participant Svc as UjianService
    participant DB as Database

    G->>C: GET hasil.index(ujian)
    C->>DB: load pengerjaans + siswa kelas
    C-->>G: rekap nilai per siswa

    G->>C: GET hasil.show(ujian, pengerjaan)
    C->>DB: load jawabans + soal + opsi + pelanggaran
    C-->>G: detail koreksi per soal

    G->>C: POST nilaiEsai(pengerjaan, jawaban, skor)
    C->>C: validasi skor 0..poin soal
    C->>DB: update JawabanSiswa(skor)
    C->>Svc: rekalkulasiNilai(pengerjaan)
    alt semua esai sudah ada skor
        Svc->>DB: hitung nilai_objektif + nilai_esai + nilai_total
        Svc->>DB: updateOrCreate DetailPenilaian<br/>(sinkron ke rapor)
        C-->>G: Toast "skor disimpan, nilai final"
    else masih ada esai belum dikoreksi
        Svc->>DB: nilai_total = null
        C-->>G: Toast "skor disimpan, menunggu koreksi lain"
    end
```

### 3.4 Guru Buat Ujian dari Bank Soal

```mermaid
sequenceDiagram
    actor G as Guru
    participant C as Guru/SoalController
    participant Svc as BankSoalService
    participant DB as Database

    G->>C: GET soal.index(ujian)
    C->>DB: load BankSoal milik matpel
    C-->>G: daftar bank soal + soal ujian saat ini

    alt skenario A — ambil terpilih
        G->>C: POST ambilDariBank(bank_soal_ids[])
        C->>Svc: salinKeUjian(ujian, ids)
    else skenario B — generate acak
        G->>C: POST generateDariBank(kriteria)
        C->>Svc: generate(ujian, kriteria)
        Svc->>DB: pilih N id acak by tipe/topik/kesulitan
        Svc->>Svc: salinKeUjian(ujian, ids)
    end

    Svc->>DB: transaction {
    loop tiap BankSoal terpilih
        Svc->>DB: create Soal (snapshot: tipe, pertanyaan,<br/>poin, kunci_isian, bank_soal_id)
        loop tiap BankOpsiSoal
            Svc->>DB: create OpsiSoal (teks, benar, urutan)
        end
    end
    Note over Svc,DB: } snapshot — perubahan bank<br/>tidak memengaruhi soal ujian
    C-->>G: Toast "N soal disalin dari bank"
```

---
## 4. Diagram Aktivitas

### 4.1 Alur Pengerjaan Ujian — Siswa

```mermaid
flowchart TD
    START((Mulai)) --> LOGIN[Login sebagai Siswa]
    LOGIN --> LIHAT[Buka daftar ujian]
    LIHAT --> PILIH{Pilih ujian}
    PILIH --> CEK_JADWAL{Ujian sedang<br/>berlangsung?}
    CEK_JADWAL -- Tidak --> TOLAK_JADWAL[Tampilkan pesan<br/>belum dibuka / berakhir]
    TOLAK_JADWAL --> END1((Selesai))
    CEK_JADWAL -- Ya --> CEK_TOKEN{Butuh token?}
    CEK_TOKEN -- Ya --> INPUT_TOKEN[Input token]
    INPUT_TOKEN --> VALID_TOKEN{Token valid<br/>& sudah dirilis?}
    VALID_TOKEN -- Tidak --> TOLAK_TOKEN[Pesan token salah]
    TOLAK_TOKEN --> END1
    CEK_TOKEN -- Tidak --> CEK_ATTEMPT
    VALID_TOKEN -- Ya --> CEK_ATTEMPT{Sisa attempt?}
    CEK_ATTEMPT -- Tidak --> TOLAK_ATTEMPT[Pesan kesempatan habis]
    TOLAK_ATTEMPT --> END1
    CEK_ATTEMPT -- Ya --> BUAT_SESI[Buat sesi pengerjaan<br/>status=berlangsung]

    BUAT_SESI --> FULLSCREEN{Ujian wajib<br/>fullscreen?}
    FULLSCREEN -- Ya --> GATE[Tampilkan gate fullscreen]
    GATE --> KLIK{Klik masuk<br/>fullscreen?}
    KLIK -- Ya --> KERJAKAN
    KLIK -- Tidak --> CATAT_BYPASS[Catat pelanggaran<br/>tanpa_fullscreen]
    CATAT_BYPASS --> KERJAKAN[Mulai mengerjakan soal]
    FULLSCREEN -- Tidak --> KERJAKAN

    KERJAKAN --> JAWAB[Jawab soal satu per satu<br/>simpan otomatis per soal]
    JAWAB --> PAR{Paralel}
    PAR --> TIMER[Timer berjalan<br/>tampilkan sisa waktu]
    PAR --> AWASI[Awasi proctoring:<br/>blur / keluar fullscreen]

    AWASI --> LANGGAR{Ada pelanggaran?}
    LANGGAR -- Ya --> CATAT[Catat PelanggaranLog<br/>increment counter]
    CATAT --> CEK_AMBANG{Melebihi ambang?}
    CEK_AMBANG -- Ya --> DISKUALIFIKASI[Finalisasi:<br/>status=diskualifikasi]
    DISKUALIFIKASI --> NILAI_OTOMATIS
    CEK_AMBANG -- Tidak --> JAWAB
    LANGGAR -- Tidak --> JAWAB

    TIMER --> WAKTU_HABIS{Waktu habis?}
    WAKTU_HABIS -- Ya --> AUTO_SUBMIT[Finalisasi:<br/>status=auto_submit]
    AUTO_SUBMIT --> NILAI_OTOMATIS[Nilai otomatis<br/>soal objektif]
    WAKTU_HABIS -- Tidak --> KUMPUL{Siswa klik<br/>kumpulkan?}
    KUMPUL -- Tidak --> JAWAB
    KUMPUL -- Ya --> SUBMIT_MANUAL[Finalisasi:<br/>status=selesai]
    SUBMIT_MANUAL --> NILAI_OTOMATIS

    NILAI_OTOMATIS --> HITUNG[Hitung nilai_objektif<br/>+ nilai_esai bila lengkap]
    HITUNG --> ADA_ESAI_PENDING{Ada esai belum<br/>dikoreksi?}
    ADA_ESAI_PENDING -- Ya --> TUNGGU[Nilai total = null<br/>menunggu koreksi guru]
    TUNGGU --> GURU_KOREKSI[Guru koreksi esai]
    GURU_KOREKSI --> HITUNG
    ADA_ESAI_PENDING -- Tidak --> SINKRON[Sinkron ke DetailPenilaian]
    SINKRON --> LIHAT_HASIL{Siswa boleh lihat<br/>tampilkan_hasil?}
    LIHAT_HASIL -- Ya --> TAMPIL_NILAI[Tampilkan nilai]
    LIHAT_HASIL -- Tidak --> SEMBUNYI[Nilai disembunyikan]
    TAMPIL_NILAI --> END2((Selesai))
    SEMBUNYI --> END2
```

### 4.2 Alur Koreksi — Guru

```mermaid
flowchart TD
    START((Mulai)) --> LOGIN[Login sebagai Guru]
    LOGIN --> BUKA_REKAP[Buka rekap hasil ujian]
    BUKA_REKAP --> PILIH_SISWA{Pilih siswa}
    PILIH_SISWA --> LIHAT_DETAIL[Lihat detail jawaban<br/>+ log pelanggaran]
    LIHAT_DETAIL --> ADA_ESAI{Ada soal esai?}
    ADA_ESAI -- Tidak --> SELESAI_OTOMATIS[Nilai sudah final<br/>otomatis]
    SELESAI_OTOMATIS --> END((Selesai))
    ADA_ESAI -- Ya --> PER_SOAL[Untuk tiap jawaban esai]
    PER_SOAL --> BACA[Baca jawaban siswa]
    BACA --> BERI_SKOR[Beri skor 0..poin]
    BERI_SKOR --> SIMPAN[Simpan skor]
    SIMPAN --> HITUNG_ULANG[Sistem hitung ulang<br/>rekalkulasiNilai]
    HITUNG_ULANG --> MASIH_ADA{Masih ada esai<br/>belum dikoreksi?}
    MASIH_ADA -- Ya --> PER_SOAL
    MASIH_ADA -- Tidak --> FINAL[Hitung nilai_total final]
    FINAL --> SINKRON[Sinkron ke DetailPenilaian<br/>sumber = kategori ujian]
    SINKRON --> END
```

---
## 5. Diagram State Machine

### 5.1 Status Ujian

```mermaid
stateDiagram-v2
    [*] --> draft : buat ujian
    draft --> terbit : terbit()\n(min. 1 soal)
    terbit --> draft : tarik ke draft
    terbit --> terbit : ubah jadwal/token/\nsoal (selama belum dikerjakan)

    note right of draft
        Ujian belum bisa
        dikerjakan siswa
    end note
    note right of terbit
        Siswa bisa mulai bila
        jendela jadwal aktif
        (ditentukan tanggal_mulai /
        tanggal_selesai, bukan status)
    end note
```

### 5.2 Status UjianPengerjaan

```mermaid
stateDiagram-v2
    [*] --> berlangsung : mulai()\nbuat sesi
    berlangsung --> berlangsung : simpanJawaban()\nlapor() pelanggaran\n(di bawah ambang)
    berlangsung --> selesai : submit()\nmanual oleh siswa
    berlangsung --> auto_submit : timer habis\n(finalisasi otomatis)
    berlangsung --> diskualifikasi : pelanggaran ≥ maks_pelanggaran\n(finalisasi otomatis)
    selesai --> [*]
    auto_submit --> [*]
    diskualifikasi --> [*]

    note right of berlangsung
        gradeObjektif() +
        rekalkulasiNilai()
        berjalan saat finalisasi
        ke salah satu status akhir
    end note
```

**Aturan transisi penting:**
- `berlangsung` → status akhir bersifat **final** (tidak bisa kembali).
- Guard edit soal: `SoalController@update/destroy` ditolak bila `ujian.pengerjaans()->exists()`.
- Guard hapus ujian: `UjianController@destroy` ditolak bila sudah ada pengerjaan.
- Esai tanpa jawaban (`JawabanSiswa` tidak ada) dihitung **0**, bukan pending — nilai tidak macet.

---
## 6. BPMN 2.0 — Proses Bisnis Modul CBT

> Notasi: diagram digambar sebagai flowchart Mermaid dengan **pool** (subgraph) per partisipan mengikuti semantik BPMN 2.0 OMG — start/end event (lingkaran), task (persegi), exclusive gateway (diamond `X`), parallel gateway (diamond `+`), message flow (panah putus-putus antar pool), sequence flow (panah solid dalam pool).

### 6.1 Proses Pelaksanaan Ujian

Kolaborasi tiga pool: **Guru (Pengawas)**, **Siswa**, **Sistem**.

```mermaid
flowchart TB
    subgraph POOL_GURU["POOL: Guru (Pengawas)"]
        direction TB
        G1((Start)) --> G2[Buat & terbitkan ujian]
        G2 --> G3[Generate token ujian]
        G3 --> G4[Rilis token saat waktu tiba]
        G4 -.->|message: token dirilis| S4
        G4 --> G5[Awasi pelaksanaan]
        G5 -.->|message: hasil final| G6
        G6[Terima rekap nilai] --> G7((End))
    end

    subgraph POOL_SISWA["POOL: Siswa"]
        direction TB
        S1((Start)) --> S2[Buka daftar ujian]
        S2 --> S3{Butuh token?}
        S3 -- Ya --> S4[Input token]
        S4 --> S5{Token valid?}
        S5 -- Tidak --> S6[Lihat pesan error]
        S6 --> S2
        S3 -- Tidak --> S7
        S5 -- Ya --> S7[Masuk mode fullscreen]
        S7 --> S8[Kerjakan soal]
        S8 --> S9{Paralel}
        S9 --> S10[Jawab & simpan per soal]
        S9 --> S11[Timer berjalan]
        S10 --> S12{Kumpulkan?}
        S12 -- Tidak --> S8
        S11 --> S13{Waktu habis?}
        S13 -- Ya --> S14((End: auto-submit))
        S13 -- Tidak --> S12
        S12 -- Ya --> S15[Submit manual]
        S15 --> S16((End: selesai))
        S14 -.->|message: sesi berakhir| SYS4
        S16 -.->|message: sesi berakhir| SYS4
    end

    subgraph POOL_SISTEM["POOL: Sistem"]
        direction TB
        SYS1((Start)) --> SYS2[Buat sesi pengerjaan]
        SYS2 --> SYS3{Paralel}
        SYS3 --> SYS4[Terima akhir sesi]
        SYS3 --> SYS5[Catat pelanggaran proctoring]
        SYS5 --> SYS6{Melebihi ambang?}
        SYS6 -- Ya --> SYS7[Diskualifikasi otomatis]
        SYS7 --> SYS4
        SYS6 -- Tidak --> SYS5
        SYS4 --> SYS8[Nilai otomatis soal objektif]
        SYS8 --> SYS9[Ada esai pending?]
        SYS9 -- Ya --> SYS10[Tunggu koreksi guru]
        SYS10 -.->|message: minta koreksi| G5
        SYS9 -- Tidak --> SYS11[Hitung nilai total]
        SYS11 --> SYS12[Sinkron ke penilaian]
        SYS12 -.->|message: hasil final| G6
        SYS12 --> SYS13((End))
    end
```

### 6.2 Proses Penilaian & Koreksi

Kolaborasi dua pool: **Guru**, **Sistem**.

```mermaid
flowchart TB
    subgraph POOL_G2["POOL: Guru"]
        direction TB
        A1((Start)) --> A2[Buka rekap hasil ujian]
        A2 --> A3[Pilih siswa & buka detail]
        A3 --> A4{Ada soal esai?}
        A4 -- Tidak --> A5[Nilai final otomatis]
        A5 --> A6((End))
        A4 -- Ya --> A7[Baca tiap jawaban esai]
        A7 --> A8[Beri skor 0..poin]
        A8 --> A9[Simpan skor]
        A9 -.->|message: skor disimpan| B3
        A9 --> A10{Masih ada esai?}
        A10 -- Ya --> A7
        A10 -- Tidak --> A11[Verifikasi nilai final]
        A11 --> A12((End))
    end

    subgraph POOL_SYS2["POOL: Sistem"]
        direction TB
        B1((Start)) --> B2[Hitung nilai objektif]
        B2 --> B3[Terima skor esai]
        B3 --> B4[Hitung ulang nilai]
        B4 --> B5{Semua esai terkoreksi?}
        B5 -- Tidak --> B6[Nilai total = null<br/>status menunggu]
        B6 --> B3
        B5 -- Ya --> B7[Hitung nilai_total final]
        B7 --> B8[Sinkron ke DetailPenilaian]
        B8 --> B9((End))
    end
```

### 6.3 Proses Pengelolaan Bank Soal

Kolaborasi dua pool: **Guru**, **Admin**.

```mermaid
flowchart TB
    subgraph POOL_G3["POOL: Guru"]
        direction TB
        C1((Start)) --> C2[Buat soal bank<br/>per mata pelajaran]
        C2 --> C3[Isi topik & kesulitan]
        C3 --> C4[Simpan ke bank soal]
        C4 --> C5{Butuh soal untuk ujian?}
        C5 -- Ya --> C6[Pilih: ambil terpilih<br/>atau generate acak]
        C6 --> C7[Salin snapshot ke ujian]
        C7 --> C8((End))
        C5 -- Tidak --> C8
    end

    subgraph POOL_A3["POOL: Admin"]
        direction TB
        D1((Start)) --> D2[Kelola bank soal<br/>semua mapel]
        D2 --> D3{Validasi kualitas?}
        D3 -- Perlu perbaikan --> D4[Minta revisi ke guru]
        D4 -.->|message: revisi| C2
        D3 -- OK --> D5((End))
    end
```

### Legenda Elemen BPMN

| Simbol Mermaid | Elemen BPMN 2.0 |
|---|---|
| `(( ))` | Start / End Event |
| `[ ]` | Task (aktivitas) |
| `{ }` | Exclusive Gateway (XOR) |
| `{Paralel}` | Parallel Gateway (AND) |
| `-.->` antar subgraph | Message Flow antar pool |
| `-->` dalam subgraph | Sequence Flow dalam pool |

---

## Referensi Silang

- Modul non-CBT: `docs/uml/00-index.md` s.d. `docs/uml/08-deployment.md`, `docs/UML-APLIKASI.md`
- Kode sumber: `app/Models/Ujian.php`, `app/Models/Soal.php`, `app/Models/UjianPengerjaan.php`, `app/Models/JawabanSiswa.php`, `app/Models/PelanggaranLog.php`, `app/Models/BankSoal.php`, `app/Models/PeriodeUjian.php`, `app/Services/UjianService.php`, `app/Services/BankSoalService.php`
- Controller: `app/Http/Controllers/{Admin,Guru,Siswa}/UjianController.php`, `Guru/{SoalController,BankSoalController,HasilUjianController}.php`

*Dokumen ini dibuat berdasarkan kode sumber pada branch `redesign-dashboard` dan mengikuti OMG UML 2.5.1 serta OMG BPMN 2.0.*
