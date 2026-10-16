# Requirement Fitur Penilaian Siswa

## 1. Tujuan

Mengintegrasikan tugas ke dalam sistem penilaian sekolah:

- Guru memberi nilai pada tugas siswa → otomatis masuk sistem penilaian
- Guru dapat input nilai lain (UH/PTS/Sikap) untuk kelas & matpel yang diampu
- Siswa dapat melihat nilai tugasnya
- Admin mengelola master penilaian dan melihat semua nilai

## 2. Peran & Alur

### Admin

- Kelola master penilaian (nama, tipe: `kognitif`/`sikap`/`tugas`/`cbt`, `nilai_maks`, `bobot`, `aktif`)
- Input nilai manual: pilih penugasan (guru + kelas + matpel) → daftar siswa → input nilai
- Melihat semua nilai (manual & dari tugas), dengan badge sumber `[Manual]` / `[Dari Tugas]`
- Bisa edit nilai apa pun (kuasa penuh)
- Hapus penilaian diblokir jika sudah punya nilai

### Guru

- Buat tugas (punya `poin` maksimum) → sistem otomatis membuat Penilaian `Tugas: {judul}`
- Buka halaman Pengumpulan → lihat jawaban → kasih nilai (hanya siswa yang **SUDAH mengumpulkan**, `0 ≤ nilai ≤ poin`)
- Nilai tersimpan di `tugas_pengumpulans.nilai` + ter-sync ke `detail_penilaian` (`sumber='tugas'`, `guru_id` dari user)
- Menu "Penilaian" baru: pilih jenis penilaian + penugasan sendiri → input/edit nilai manual (`sumber='manual'`)
- Hapus tugas = nilai ikut terhapus (dengan konfirmasi di UI)

### Siswa

- Lihat badge `Nilai: X/poin` di daftar tugas & detail tugas
- Sebelum dinilai → "Belum dinilai"
- Mengganti jawaban **SETELAH dinilai** → nilai dihapus, guru harus menilai ulang

## 3. Skema Database

### `penilaian` (master — template lintas tahun, tanpa `tahun_ajaran_id`)

| kolom | tipe |
|---|---|
| id | bigint PK |
| nama | string |
| deskripsi | text nullable |
| tipe | enum(`kognitif`, `sikap`, `tugas`, `cbt`) |
| nilai_maks | integer |
| bobot | decimal(5,2) |
| aktif | boolean |
| sumber | enum(`manual`, `tugas`) default `manual` **← BARU** |

### `tugases`

- `+ penilaian_id` FK nullable → `penilaian` (cascade) **← BARU**
- `poin` sudah ada (default 100)

### `tugas_pengumpulans`

- `+ nilai` decimal(5,2) nullable **← BARU**

### `detail_penilaian`

| kolom | tipe |
|---|---|
| id | bigint PK |
| penilaian_id | FK → `penilaian` (cascade) |
| guru_kelas_id | FK → `guru_kelas` **← BARU** |
| tahun_ajaran_id | FK → `tahun_ajaran` **← BARU** |
| siswa_nisn | FK → `siswa` (varchar 10) |
| guru_id | FK nullable → `gurus` (di-set otomatis) |
| nilai | decimal(5,2) |
| sumber | enum(`manual`, `tugas`, `cbt`) |
| keterangan | text nullable |
| UNIQUE | `(penilaian_id, guru_kelas_id, siswa_nisn)` **← BARU** |

### Relasi

```
penilaian 1—N detail_penilaian N—1 guru_kelas —N—1 tahun_ajaran
tugases 1—1 penilaian (auto-create saat buat tugas)
tugas_pengumpulans 1—1 detail_penilaian (sync via penilaian_id + siswa_nisn)
```

## 4. Aturan Bisnis

1. Nilai tugas hanya bisa diinput untuk siswa yang sudah mengumpulkan
2. Range nilai: `0 ≤ nilai ≤ poin` (tugas) / `nilai_maks` (penilaian)
3. Jawaban diganti setelah dinilai → nilai direset, guru re-nilai
4. Hapus tugas → penilaian & nilai terkait ikut terhapus (konfirmasi UI)
5. Hapus penilaian dengan nilai → diblokir (kecuali penilaian dari tugas yang ikut terhapus saat tugas dihapus)
6. `guru_id` tercatat otomatis dari user yang menilai
7. Nilai per `(penilaian + guru_kelas + siswa)` unik → nilai per matpel terpisah
8. Tahun ajaran diambil dari `guru_kelas` (tugas) / dipilih saat input manual
9. Admin input nilai: pilih penugasan (`guru_kelas`), bukan cuma kelas

## 5. Route Baru

- `app.guru.tugas.nilai` (POST/PUT) — simpan nilai tugas + sync ke `detail_penilaian`
- `app.guru.penilaian.index` — daftar jenis penilaian + penugasan saya
- `app.guru.penilaian.show` — tabel siswa + nilai per `(penilaian, guru_kelas)`
- `app.guru.penilaian.store` / `update` — simpan/edit nilai manual
- `admin.penilaian.*` — penyesuaian: filter pilih penugasan

## 6. Migrasi (5)

1. `add_nilai_to_tugas_pengumpulans_table`
2. `add_penilaian_id_to_tugases_table`
3. `add_sumber_to_penilaian_table`
4. `add_guru_kelas_id_to_detail_penilaian_table` (+ unique index baru)
5. `add_tahun_ajaran_id_to_detail_penilaian_table`

## 7. Tests

- **TugasNilaiTest**: auto-create penilaian, simpan nilai, sync `detail_penilaian`, validasi range, reset nilai saat jawaban diganti, otorisasi guru pemilik
- **GuruPenilaianTest**: index/input nilai manual, scope penugasan sendiri, validasi
- **PenilaianTest**: CRUD admin, redirect fix, validasi
- **DetailPenilaianTest**: filter penugasan, `nilai ≤ nilai_maks`, `guru_id` tersimpan

## 8. Konvensi UI

Semua halaman baru/ubah mengikuti pola halaman lain di aplikasi:

- `PageHeader` (title + subtitle + `actions` snippet)
- Container `<div class="container-fluid px-0">`
- `Card class="border rounded-1 shadow-none"` + `CardBody`
- `Badge color=... pill` + ikon `bi bi-...` untuk status
- `Select` component + search input + query string untuk filter
- Tabel di dalam Card dengan `table-hover align-middle`
- Empty state: Card dengan ikon besar + teks muted di tengah
- Konfirmasi hapus via `confirm` dari `@/lib/confirm.svelte`
- Form memakai `FormGroup/Label/Input` sveltestrap