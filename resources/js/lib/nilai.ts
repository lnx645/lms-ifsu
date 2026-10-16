/**
 * Utilitas penilaian: predikat A–D dan rata-rata.
 * Satu-satunya sumber kebenaran untuk perhitungan nilai.
 */

export type Predikat = {
    huruf: 'A' | 'B' | 'C' | 'D';
    kelas: string;
    label: string;
};

/**
 * Predikat berdasarkan persentase nilai terhadap nilai maksimum.
 * A >= 86%, B >= 71%, C >= 56%, sisanya D.
 */
export function predikat(
    nilai: number | null,
    maks: number | null,
): Predikat | null {
    if (nilai === null || !maks || maks <= 0) {
        return null;
    }

    const pct = (nilai / maks) * 100;

    if (pct >= 86) {
        return { huruf: 'A', kelas: 'text-success', label: 'Sangat Baik' };
    }
    if (pct >= 71) {
        return { huruf: 'B', kelas: 'text-primary', label: 'Baik' };
    }
    if (pct >= 56) {
        return { huruf: 'C', kelas: 'text-warning', label: 'Cukup' };
    }
    return { huruf: 'D', kelas: 'text-danger', label: 'Kurang' };
}

/**
 * Rata-rata nilai non-null, dibulatkan 2 desimal. Null saat tidak ada nilai.
 */
export function rataRata(nilai: readonly (number | null)[]): number | null {
    const terisi = nilai.filter((n): n is number => n !== null);
    if (!terisi.length) {
        return null;
    }
    return Math.round((terisi.reduce((a, b) => a + b, 0) / terisi.length) * 100) / 100;
}
