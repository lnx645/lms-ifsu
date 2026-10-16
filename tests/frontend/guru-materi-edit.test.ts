import { router, useForm } from '@inertiajs/svelte';
import {
    fireEvent,
    render,
    screen,
    within,
} from '@testing-library/svelte';
import { tick } from 'svelte';
import { describe, expect, it } from 'vitest';
import Index from '@/pages/guru/Materi/Index.svelte';

function withinEditModal() {
    const content = screen
        .getByText('Edit Materi')
        .closest('.modal-content');

    return content ? within(content) : screen;
}

const baseProps = {
    materis: {
        data: [
            {
                id: 1,
                judul: 'Bab 1: Bilangan',
                deskripsi: 'Deskripsi lama',
                konten: null,
                file_name: 'bab-1.pdf',
                file_size: 2048,
                kelas: 'X-RPL-1',
                matpel: 'Matematika',
                dibuat_pada: '12 Agu 2026',
            },
        ],
        current_page: 1,
        last_page: 1,
        total: 1,
        per_page: 10,
        from: 1,
        to: 1,
    },
    penugasan: [
        { value: 10, label: 'X-RPL-1 — Matematika' },
        { value: 20, label: 'X-RPL-2 — Bahasa Inggris' },
    ],
    katalog: null,
    katalogFilters: { tahunAjaran: [], kelas: [], matpel: [] },
    filters: { guru_kelas_id: null, q: '' },
    editMateri: null,
};

describe('guru/Materi/Index edit materi', () => {
    it('menampilkan tombol edit di setiap baris materi', () => {
        render(Index, { props: baseProps });

        expect(
            screen.getByText('Bab 1: Bilangan'),
        ).toBeInTheDocument();
        expect(screen.getByTitle('Edit materi')).toBeInTheDocument();
    });

    it('tombol edit memuat data materi lewat partial reload', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit materi'));

        expect(router.get).toHaveBeenCalledWith(
            '/app/guru/materi/1/edit',
            {},
            expect.objectContaining({ only: ['editMateri'] }),
        );

        await rerender({
            ...baseProps,
            editMateri: {
                id: 1,
                guru_kelas_id: 10,
                judul: 'Bab 1: Bilangan',
                deskripsi: 'Deskripsi lama',
                konten: '<p>Isi lama</p>',
                file_name: 'bab-1.pdf',
                file_size: 2048,
            },
        });
    });

    it('modal edit menampilkan data yang sudah diisi dan berkas saat ini', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit materi'));
        await rerender({
            ...baseProps,
            editMateri: {
                id: 1,
                guru_kelas_id: 10,
                judul: 'Bab 1: Bilangan',
                deskripsi: 'Deskripsi lama',
                konten: '<p>Isi lama</p>',
                file_name: 'bab-1.pdf',
                file_size: 2048,
            },
        });
        await tick();

        expect(screen.getByText('Edit Materi')).toBeInTheDocument();
        expect(
            withinEditModal().getByLabelText('Judul Materi'),
        ).toBeInTheDocument();
        expect(
            withinEditModal().getByLabelText(/Deskripsi/),
        ).toBeInTheDocument();
        expect(
            withinEditModal().getByText(
                /Berkas saat ini: bab-1\.pdf \(2\.0 KB\)/,
            ),
        ).toBeInTheDocument();

        const editForm = useForm.mock.results[1]?.value;
        expect(editForm).toBeDefined();
        expect(editForm.judul).toBe('Bab 1: Bilangan');
        expect(editForm.deskripsi).toBe('Deskripsi lama');
        expect(editForm.konten).toBe('<p>Isi lama</p>');
        expect(editForm.guru_kelas_id).toBe(10);
        expect(editForm.file).toBeNull();
    });

    it('menyimpan perubahan mengirim PUT ke endpoint update', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit materi'));
        await rerender({
            ...baseProps,
            editMateri: {
                id: 1,
                guru_kelas_id: 10,
                judul: 'Bab 1: Bilangan',
                deskripsi: 'Deskripsi lama',
                konten: '<p>Isi lama</p>',
                file_name: 'bab-1.pdf',
                file_size: 2048,
            },
        });
        await tick();

        const judulInput =
            withinEditModal().getByLabelText('Judul Materi');
        await fireEvent.input(judulInput, {
            target: { value: 'Bab 1: Bilangan (Revisi)' },
        });
        await fireEvent.click(
            screen.getByRole('button', { name: /Simpan Perubahan/ }),
        );

        const editForm = useForm.mock.results[1]?.value;
        expect(editForm).toBeDefined();
        expect(editForm.put).toHaveBeenCalledTimes(1);
        expect(editForm.put.mock.calls[0][0]).toBe(
            '/app/guru/materi/1',
        );
        expect(editForm.put.mock.calls[0][1]).toEqual(
            expect.objectContaining({ preserveScroll: true }),
        );

        expect(editForm.calls[0].payload).toEqual(
            expect.objectContaining({
                guru_kelas_id: 10,
                judul: 'Bab 1: Bilangan (Revisi)',
                deskripsi: 'Deskripsi lama',
                file: null,
            }),
        );

        expect(
            screen.queryByText('Edit Materi'),
        ).not.toBeInTheDocument();
    });

    it('tombol batal menutup modal tanpa mengirim request update', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit materi'));
        await rerender({
            ...baseProps,
            editMateri: {
                id: 1,
                guru_kelas_id: 10,
                judul: 'Bab 1: Bilangan',
                deskripsi: null,
                konten: null,
                file_name: null,
                file_size: 0,
            },
        });
        await tick();

        await fireEvent.click(
            screen.getByRole('button', { name: 'Batal' }),
        );

        const editForm = useForm.mock.results[1]?.value;
        expect(editForm.put).not.toHaveBeenCalled();
        expect(
            screen.queryByText('Edit Materi'),
        ).not.toBeInTheDocument();
    });
});
