export type MateriDetail = {
    id: number;
    judul: string;
    deskripsi: string | null;
    has_konten: boolean;
    file_name: string | null;
    file_size: number;
    mime_type: string | null;
    kelas: string | null;
    matpel: string | null;
    guru: string | null;
    dibuat_pada: string;
};

export type ViewerKind = 'pdf' | 'docx' | 'xlsx' | 'pptx';

export const MIME_VIEWERS: Record<string, ViewerKind> = {
    'application/pdf': 'pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'pptx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xlsx',
    'application/vnd.ms-excel': 'xlsx',
};

export const VIEWER_FILE_STYLES: Record<ViewerKind, { icon: string; cssClass: string }> = {
    pdf: { icon: 'bi-file-earmark-pdf', cssClass: 'detail-file-icon--pdf' },
    docx: { icon: 'bi-file-earmark-word', cssClass: 'detail-file-icon--word' },
    xlsx: { icon: 'bi-file-earmark-excel', cssClass: 'detail-file-icon--excel' },
    pptx: { icon: 'bi-file-earmark-ppt', cssClass: 'detail-file-icon--ppt' },
};

const MIME_LABELS: Record<string, string> = {
    'application/pdf': 'PDF',
    'application/msword': 'DOC',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
    'application/vnd.ms-powerpoint': 'PPT',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
    'application/vnd.ms-excel': 'XLS',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
    'application/zip': 'ZIP',
    'text/plain': 'TXT',
    'image/jpeg': 'JPG',
    'image/png': 'PNG',
    'audio/mpeg': 'MP3',
    'video/mp4': 'MP4',
};

export function viewerKindFor(fileName: string | null, mimeType: string | null): ViewerKind | null {
    if (!fileName) {
        return null;
    }

    return MIME_VIEWERS[mimeType ?? ''] ?? null;
}

export function fileStyleFor(kind: ViewerKind | null): { icon: string; cssClass: string } | null {
    return kind ? VIEWER_FILE_STYLES[kind] : null;
}

export function formatBytes(bytes: number): string {
    if (!bytes) {
        return '—';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }

    return `${size.toFixed(size >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
}

export function formatFileType(mime: string | null, fileName: string | null): string {
    if (mime && MIME_LABELS[mime]) {
        return MIME_LABELS[mime];
    }

    const extension = fileName?.split('.').pop()?.toUpperCase();
    if (extension && extension.length <= 5) {
        return extension;
    }

    return mime?.split('/')[1]?.toUpperCase() ?? 'BERKAS';
}

export function stripHtml(html: string | null): string {
    if (!html) {
        return '';
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');

    return doc.body.textContent?.trim() ?? '';
}
