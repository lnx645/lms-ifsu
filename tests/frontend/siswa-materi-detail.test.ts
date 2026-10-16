import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import Detail from '@/pages/siswa/Materi/Detail.svelte';

const baseProps = {
    materi: {
        id: 1,
        judul: 'Bab 1: Bilangan',
        matpel: 'Matematika',
        kelas: 'X-RPL-1',
        guru: 'Pak Budi',
        dibuat_pada: '12 Agu 2026',
        file_name: 'bab-1.pdf',
        file_size: 2048,
        mime_type: 'application/pdf',
        has_konten: true,
        deskripsi: 'Materi pengantar',
    },
    konten: '<p>Isi materi</p>',
};

describe('siswa/Materi/Detail', () => {
    it('menampilkan judul, matpel, dan kelas di hero', () => {
        render(Detail, { props: baseProps });

        expect(
            screen.getByRole('heading', { name: 'Bab 1: Bilangan' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Matematika')).toBeInTheDocument();
        expect(screen.getByText('X-RPL-1')).toBeInTheDocument();
        expect(document.body.textContent).toContain('Pak Budi');
    });

    it('hero tidak lagi menampilkan tombol Unduh Berkas (redundan dengan tab Lampiran)', () => {
        render(Detail, { props: baseProps });

        expect(
            screen.queryByRole('button', { name: /Unduh Berkas/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('Unduh Berkas'),
        ).not.toBeInTheDocument();
    });

    it('tombol unduh hanya muncul di tab Lampiran', async () => {
        render(Detail, { props: baseProps });

        const lampiranTab = screen.getByRole('button', {
            name: /Lampiran/,
        });
        await fireEvent.click(lampiranTab);

        const unduh = screen.getByRole('link', { name: /Unduh/ });
        expect(unduh).toHaveAttribute('href', '/app/materi/1/unduh');
    });

    it('link kembali mengarah ke daftar materi', () => {
        render(Detail, { props: baseProps });

        expect(
            screen.getByRole('link', { name: /Daftar Materi/ }),
        ).toHaveAttribute('href', '/app/materi');
    });
});
