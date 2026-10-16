/**
 * Navigasi berbasis query param `guru_kelas_id` untuk halaman penilaian
 * (rekap guru, filter input nilai admin).
 */
import { router } from '@inertiajs/svelte';

const PARAM = 'guru_kelas_id';

/**
 * Bangun URL ke `baseUrl` dengan query `guru_kelas_id` yang sudah terisi.
 */
export function withPenugasanParam(
    baseUrl: string,
    guruKelasId: number | string,
): string {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set(PARAM, String(guruKelasId));

    return url.pathname + url.search;
}

/**
 * Pindah ke halaman yang sama dengan `guru_kelas_id` dari dropdown.
 */
export function changePenugasan(event: Event): void {
    const value = (event.currentTarget as HTMLSelectElement).value;
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(PARAM, value);
    } else {
        url.searchParams.delete(PARAM);
    }
    router.visit(url.pathname + url.search);
}

/**
 * Kembali ke halaman yang sama tanpa filter penugasan.
 */
export function resetPenugasan(): void {
    const url = new URL(window.location.href);
    url.searchParams.delete(PARAM);
    router.visit(url.pathname + url.search);
}
