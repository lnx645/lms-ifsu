export type JenisPengumpulan = 'file' | 'teks' | 'keduanya';

export type StatusTugas = 'belum' | 'terkumpul' | 'terlambat' | 'tutup';

export type TugasFormState = {
    guru_kelas_id: number | null;
    judul: string;
    deskripsi: string;
    tanggal_terbit: string;
    deadline: string;
    jenis_pengumpulan: JenisPengumpulan;
    poin: number;
    file: File | null;
    errors: Record<string, string>;
    processing: boolean;
};

export const ALLOWED_EXTENSIONS = [
    'pdf',
    'doc',
    'docx',
    'ppt',
    'pptx',
    'xls',
    'xlsx',
    'jpg',
    'jpeg',
    'png',
    'zip',
    'mp4',
    'mp3',
    'txt',
];

export const MAX_FILE_SIZE = 20 * 1024 * 1024;

export const ACCEPT_ATTRIBUTE = ALLOWED_EXTENSIONS.map(
    (ext) => `.${ext}`,
).join(',');

export const PENGGUMPULAN_INFO: Record<
    JenisPengumpulan,
    { label: string; icon: string; warna: string }
> = {
    file: { label: 'Upload File', icon: 'bi-upload', warna: 'text-primary' },
    teks: {
        label: 'Tulis Langsung',
        icon: 'bi-pencil-square',
        warna: 'text-success',
    },
    keduanya: {
        label: 'File & Teks',
        icon: 'bi-file-earmark-plus',
        warna: 'text-primary',
    },
};

export const STATUS_TUGAS_INFO: Record<
    StatusTugas,
    { color: string; label: string; icon: string }
> = {
    belum: {
        color: 'primary',
        label: 'Belum Dikerjakan',
        icon: 'bi-clock',
    },
    terkumpul: {
        color: 'success',
        label: 'Terkumpul',
        icon: 'bi-check2-circle',
    },
    terlambat: {
        color: 'warning',
        label: 'Terlambat',
        icon: 'bi-exclamation-triangle',
    },
    tutup: {
        color: 'secondary',
        label: 'Melewati Batas',
        icon: 'bi-x-circle',
    },
};

export function validasiFile(file: File | null): string | null {
    if (!file) {
        return null;
    }
    const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
        return 'Format berkas tidak didukung.';
    }
    if (file.size > MAX_FILE_SIZE) {
        return 'Ukuran berkas maksimal 20 MB.';
    }
    return null;
}

export function fileIcon(fileName: string | null): {
    icon: string;
    color: string;
} {
    if (!fileName) {
        return {
            icon: 'bi-file-earmark',
            color: 'text-secondary',
        };
    }
    const ext = fileName.split('.').pop()?.toLowerCase() ?? '';
    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-image',
            color: 'text-info',
        };
    }
    if (['mp4'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-play',
            color: 'text-danger',
        };
    }
    if (['mp3'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-music',
            color: 'text-danger',
        };
    }
    if (['zip'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-zip',
            color: 'text-warning',
        };
    }
    if (ext === 'pdf') {
        return {
            icon: 'bi-file-earmark-pdf',
            color: 'text-danger',
        };
    }
    if (['doc', 'docx'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-word',
            color: 'text-primary',
        };
    }
    if (['ppt', 'pptx'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-ppt',
            color: 'text-danger',
        };
    }
    if (['xls', 'xlsx'].includes(ext)) {
        return {
            icon: 'bi-file-earmark-excel',
            color: 'text-success',
        };
    }
    return {
        icon: 'bi-file-earmark-text',
        color: 'text-secondary',
    };
}

export function deadlineLewat(
    deadlineAt: string | null | undefined,
): boolean {
    return (
        !!deadlineAt && new Date(deadlineAt).getTime() < Date.now()
    );
}

export function sisaWaktu(deadlineAt: string | null): string | null {
    if (!deadlineAt) {
        return null;
    }
    const sisa = new Date(deadlineAt).getTime() - Date.now();
    if (sisa <= 0) {
        return null;
    }
    const jam = Math.floor(sisa / 3_600_000);
    if (jam < 1) {
        const menit = Math.max(1, Math.floor(sisa / 60_000));
        return `Sisa ${menit} menit`;
    }
    if (jam < 24) {
        return `Sisa ${jam} jam`;
    }
    const hari = Math.floor(jam / 24);
    return `Sisa ${hari} hari`;
}
