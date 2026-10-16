export type KategoriUjian = 'kuis' | 'uh' | 'uts' | 'uas' | 'usbk';
export type TipeSoal = 'pg' | 'multi' | 'benar_salah' | 'isian' | 'esai';
export type Kesulitan = 'mudah' | 'sedang' | 'sulit';

export const KESULITAN_INFO: Record<Kesulitan, { label: string; color: string }> = {
    mudah: { label: 'Mudah', color: 'success' },
    sedang: { label: 'Sedang', color: 'warning' },
    sulit: { label: 'Sulit', color: 'danger' },
};

export const KATEGORI_INFO: Record<
    KategoriUjian,
    { label: string; color: string }
> = {
    kuis: { label: 'Kuis', color: 'info' },
    uh: { label: 'Ulangan Harian', color: 'secondary' },
    uts: { label: 'UTS', color: 'primary' },
    uas: { label: 'UAS', color: 'warning' },
    usbk: { label: 'USBK', color: 'danger' },
};

export const DEFAULT_KATEGORI: Record<
    KategoriUjian,
    {
        acak_soal: boolean;
        acak_opsi: boolean;
        wajib_fullscreen: boolean;
        maks_pelanggaran: number;
        bobot: number;
    }
> = {
    kuis: {
        acak_soal: false,
        acak_opsi: false,
        wajib_fullscreen: false,
        maks_pelanggaran: 0,
        bobot: 1,
    },
    uh: {
        acak_soal: false,
        acak_opsi: false,
        wajib_fullscreen: false,
        maks_pelanggaran: 0,
        bobot: 2,
    },
    uts: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 5,
        bobot: 2,
    },
    uas: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 5,
        bobot: 3,
    },
    usbk: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 3,
        bobot: 3,
    },
};

// Label untuk 16 tipe bank soal (baru)
export const TIPE_BANK_LABEL: Record<string, string> = {
    pilihan_ganda: 'Pilihan Ganda',
    pilihan_ganda_kompleks: 'PG Kompleks',
    pilihan_ganda_citra: 'PG Citra',
    menjodohkan: 'Menjodohkan',
    menjodohkan_banyak: 'Menjodohkan Banyak',
    isian_singkat: 'Isian Singkat',
    uraian: 'Uraian',
    mengurutkan: 'Mengurutkan',
    benar_salah: 'Benar/Salah',
    setuju_tidak: 'Setuju/Tidak',
    matrix_text: 'Matriks Teks',
    matrix_checkbox: 'Matriks Pilihan',
    mengelompokkan: 'Mengelompokkan',
    blank_space: 'Blank Space',
    image_labeling: 'Image Labeling',
    listening: 'Listening',
};

// Helper aman: dapatkan label tipe apapun
export function labelTipeSoal(tipe: string): string {
    if (TIPE_BANK_LABEL[tipe]) return TIPE_BANK_LABEL[tipe];
    const info = (TIPE_SOAL_INFO as Record<string, { label: string }>)[tipe];
    if (info) return info.label;
    return tipe;
}

export const TIPE_SOAL_INFO: Record<
    TipeSoal,
    { label: string; icon: string; objektif: boolean; butuhOpsi: boolean }
> = {
    pg: {
        label: 'Pilihan Ganda',
        icon: 'bi-ui-radios',
        objektif: true,
        butuhOpsi: true,
    },
    multi: {
        label: 'Multi-jawaban',
        icon: 'bi-ui-checks',
        objektif: true,
        butuhOpsi: true,
    },
    benar_salah: {
        label: 'Benar / Salah',
        icon: 'bi-toggles',
        objektif: true,
        butuhOpsi: true,
    },
    isian: {
        label: 'Isian Singkat',
        icon: 'bi-input-cursor-text',
        objektif: true,
        butuhOpsi: false,
    },
    esai: {
        label: 'Esai',
        icon: 'bi-textarea-resize',
        objektif: false,
        butuhOpsi: false,
    },
};

export const STATUS_PENGERJAAN_INFO: Record<
    string,
    { label: string; color: string }
> = {    belum: { label: 'Belum', color: 'secondary' },
    berlangsung: { label: 'Berlangsung', color: 'info' },
    selesai: { label: 'Selesai', color: 'success' },
    auto_submit: { label: 'Auto-submit', color: 'warning' },
    diskualifikasi: { label: 'Diskualifikasi', color: 'danger' },
};

/**
 * Format detik menjadi HH:MM:SS untuk timer countdown.
 */
export function formatDurasi(detik: number): string {
    const d = Math.max(0, Math.floor(detik));
    const h = Math.floor(d / 3600)
        .toString()
        .padStart(2, '0');
    const m = Math.floor((d % 3600) / 60)
        .toString()
        .padStart(2, '0');
    const s = (d % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}
