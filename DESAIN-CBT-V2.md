# Desain CBT v2 — Rombak Total (Branch `fix-cbt-v2`)

> Prinsip: **sesuai standar ujian sekolah, banyak varian soal, guru gampang pakai, nggak berbelit-belit.**
> Yang lama dilupakan — desain ulang dari sudut pandang pengguna, bukan tempelan fitur.

---

## 1. Masalah Desain Lama

| Masalah | Akibat |
|---|---|
| Satu modul ujian untuk semua kebutuhan | Kuis jadi ribet (harus isi token, sesi, dll), USBK jadi longgar (bisa terbit tanpa kelengkapan) |
| Tidak ada peran panitia ujian | Ujian resmi (US/USBK) tidak ada yang bisa kelola lintas kelas |
| Tidak ada paket soal (A/B/C) | Siswa sebelahan bisa nyontek — melanggar standar integritas USBK |
| Alur guru berbelit | Bikin ujian → isi 15 field → atur sesi → atur soal → token → terbit, semua manual dan tersebar di banyak halaman |
| Admin tanggung | Bisa bikin ujian tapi tidak bisa kelola sesi/soal/token/dokumen |

---

## 2. Konsep Baru: Dua Mode Ujian

### Mode SANTAI — untuk Kuis & Ulangan Harian
- Guru bikin dalam **3 langkah**: judul → soal → terbit. Selesai.
- Token: opsional (default mati)
- Fullscreen/proctoring: opsional (default mati)
- Sesi: tidak perlu
- Tata tertib: template singkat otomatis, bisa diedit
- Nilai: langsung tampil ke siswa
- Remedial: tetap ada (sesuai KKM)

### Mode RESMI — untuk UTS, UAS, USBK/US
- Wizard **5 langkah terpandu**: info dasar → paket soal → sesi & ruang → token & tata tertib → review & terbit
- Sistem **menolak terbit** kalau belum lengkap (checklist kelengkapan):
  - [ ] Minimal 1 paket soal dengan soal cukup
  - [ ] Sesi + ruang sudah diatur
  - [ ] Token aktif
  - [ ] Tata tertib terisi
  - [ ] KKM terisi (untuk remedial)
- Kartu peserta, daftar hadir, berita acara: tersedia otomatis per sesi
- Monitoring pengawas real-time (lihat §5)

**Aturan otomatis per kategori** — guru tidak perlu mikir, sistem yang menyesuaikan.

---

## 3. Paket Soal (Varian A/B/C)

### Konsep
- Satu ujian resmi punya **beberapa paket soal** (misal Paket A, B, C)
- Setiap paket adalah **susunan soal berbeda** yang diambil dari bank soal yang sama
- Siswa otomatis dapat paket berbeda dari teman sebangkunya (distribusi round-robin atau acak per sesi)
- Semua paket **setara tingkat kesulitannya** (divalidasi via analisis butir soal)

### Cara Guru Bikin (Harus Gampang!)
**Opsi 1 — Otomatis (1 klik):**
Guru tentukan: jumlah paket (misal 3) + jumlah soal per paket (misal 40).
Sistem ambil acak dari bank soal, bagi rata ke 3 paket, acak urutan. Selesai.

**Opsi 2 — Manual:**
Guru susun sendiri tiap paket dari bank soal (drag & drop / checklist).

**Opsi 3 — Campuran:**
Sistem generate otomatis, guru tinggal tukar soal yang kurang cocok.

### Aturan Integritas
- Siswa dalam 1 sesi yang duduk berdekatan **tidak dapat paket sama** (distribusi berselang-seling)
- Kartu peserta mencantumkan **kode paket** (A/B/C)
- Berita acara mencatat distribusi paket

---

## 4. Peran: Panitia Ujian

### Peran Baru
- **Panitia Ujian** = admin/kurikulum yang ditunjuk
- Bisa bikin & kelola **ujian lintas kelas** (US/USBK)
- Akses penuh: sesi, ruang, paket soal, token, kartu peserta, daftar hadir, berita acara, monitoring
- Guru mapel tetap bisa bikin ujian untuk **kelasnya sendiri** (mode santai maupun resmi)

### Hak Akses Ringkas
| Fitur | Guru (kelas sendiri) | Panitia | Admin |
|---|---|---|---|
| Bikin kuis/UH | ✅ | ✅ | ✅ |
| Bikin UTS/UAS/USBK | ✅ (kelas sendiri) | ✅ (lintas kelas) | ✅ |
| Kelola sesi & ruang | ✅ | ✅ | ✅ |
| Paket soal | ✅ | ✅ | ✅ |
| Kartu/berita acara | ✅ | ✅ | ✅ |
| Monitoring real-time | ✅ (ujiannya) | ✅ (semua) | ✅ |

---

## 5. Monitoring Pengawas Real-Time

Dashboard live untuk pengawas/panitia saat ujian berlangsung:
- Daftar siswa per sesi: status (belum mulai / mengerjakan / selesai)
- Jumlah pelanggaran per siswa (peringatan visual)
- Sisa waktu sesi
- Tombol aksi: **reset attempt** siswa bermasalah, **tambah waktu** darurat

Teknis: polling ringan per 15 detik (bukan websocket — lebih simpel & cukup).

---

## 6. Alur Guru (Disederhanakan)

### Bikin Kuis (Mode Santai)
```
1. Klik "Buat Kuis" → isi judul + kelas + durasi
2. Tambah soal (ketik / import Excel / ambil dari bank)
3. Klik "Terbitkan" → selesai, siswa bisa kerjakan
```
**Target: < 5 menit dari nol sampai terbit.**

### Bikin USBK (Mode Resmi)
```
1. Klik "Buat Ujian Resmi" → pilih kategori (UTS/UAS/USBK)
2. Wizard 5 langkah terpandu (tidak bisa skip langkah wajib)
3. Review checklist kelengkapan → Terbitkan
```
**Target: terpandu penuh, tidak ada langkah yang terlewat.**

---

## 7. Yang Dipertahankan dari Sistem Lama
- Bank soal (1 bank → banyak ujian, salin bukan referensi) — konsep sudah benar
- Tata tertib wajib + halaman konfirmasi — sudah benar
- Server-authoritative time + auto-submit — sudah benar
- Analisis butir soal — sudah benar, dipakai untuk validasi kesetaraan paket
- Remedial berbasis KKM — sudah benar
- Sesi bergelombang + anti-bentrok ruang — sudah benar

## 8. Yang Dibuang / Diganti
- Form ujian 15-field sekaligus → diganti wizard terpandu
- Admin "tanggung" → diganti peran panitia yang jelas
- Acak soal sederhana → diganti paket soal正式
- Tidak ada monitoring → ditambah dashboard real-time
- Kategori tanpa aturan → kategori dengan aturan otomatis yang ditegakkan

---

## 9. Rencana Eksekusi (Setelah Disetujui)
1. Migrasi: tabel `paket_soal`, kolom `paket_soal_id` di pengerjaan, peran panitia
2. Backend: PaketSoalService, validasi kelengkapan per kategori, distribusi paket
3. Frontend guru: wizard mode santai (3 langkah) + wizard mode resmi (5 langkah)
4. Frontend panitia: dashboard monitoring real-time
5. Test: unit + feature untuk distribusi paket, validasi kelengkapan, hak akses
6. Migrasi data lama: ujian existing → mode santai (default aman)

---

*Dokumen ini untuk diskusi. Jangan coding sebelum disetujui uwa.*
