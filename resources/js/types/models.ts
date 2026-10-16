/**
 * Model & domain types bersama untuk halaman Inertia.
 * Kontroller mengirim JSON dengan bentuk persis seperti di sini.
 */

export type PenilaianItem = {
    id: number;
    nama: string;
    deskripsi: string | null;
    tipe: string;
    nilai_maks: number;
    bobot: number;
    aktif: boolean;
    sumber: 'manual' | 'tugas';
};

export type PenilaianInfo = {
    id: number;
    nama: string;
    deskripsi: string | null;
    tipe: string;
    nilai_maks: number;
    sumber: 'manual' | 'tugas';
};

export type PenugasanOption = {
    value: number;
    label: string;
};

export type GuruKelasInfo = {
    id: number;
    kelas: string | null;
    matpel: string | null;
};

export type SiswaNilaiItem = {
    nisn: string;
    nama: string;
    nilai: number | null;
    sumber: 'manual' | 'tugas' | null;
    keterangan: string | null;
    guru?: string | null;
};

export type RekapKolom = {
    id: number;
    nama: string;
    nilai_maks: number | null;
};

export type RekapSiswaItem = {
    nisn: string;
    nama: string;
    nilai: (number | null)[];
    rata_rata: number | null;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from?: number | null;
    to?: number | null;
};

export type JurusanRef = {
    id: number;
    name: string;
    kode?: string | null;
};

export type GuruRef = {
    id: number;
    nama_lengkap: string;
};

export type KelasTreeNode = {
    id: number;
    nama: string;
    deskripsi: string | null;
    parent_id: number | null;
    jurusan_id: number | null;
    active: boolean;
    tingkat: string | null;
    siswa_count: number;
    depth: number;
    jurusan: JurusanRef | null;
    walikelas: GuruRef | null;
    children: KelasTreeNode[];
};

export type KelasListRow = {
    id: number;
    nama: string;
    parent_id: number | null;
    jurusan_id: number | null;
    depth: number;
};

export type SelectOption = {
    value: number | string;
    label: string;
};
