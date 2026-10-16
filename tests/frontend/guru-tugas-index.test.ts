import { router, useForm } from '@inertiajs/svelte';
import {
    fireEvent,
    render,
    screen,
    within,
} from '@testing-library/svelte';
import { tick } from 'svelte';
import { describe, expect, it } from 'vitest';
import Index from '@/pages/guru/Tugas/Index.svelte';

function withinModal(headerText: string) {
    const headers = screen.getAllByText(headerText);
    const content = headers
        .map((h) => h.closest('.modal-content'))
        .find(Boolean);

    return content ? within(content as HTMLElement) : screen;
}

const baseProps = {
    tugases: {
        data: [
            {
                id: 1,
                judul: 'Tugas 1: Persamaan Linear',
                deskripsi: 'Kerjakan di buku latihan.',
                file_name: 'soal.pdf',
                file_size: 2048,
                kelas: 'X-RPL-1',
                matpel: 'Matematika',
                tanggal_terbit: '12 Agu 2026 08:00',
                deadline: '19 Agu 2026 23:59',
                deadline_at: '2026-08-19T23:59:00+07:00',
                jenis_pengumpulan: 'file' as const,
                sudah_terbit: true,
                jumlah_siswa: 3,
                jumlah_kumpul: 3,
                jumlah_terlambat: 0,
                dibuat_pada: '12 Agu 2026 08:00',
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
    filters: { guru_kelas_id: null, q: '' },
    editTugas: null,
};

const editTugasProps = {
    ...baseProps,
    editTugas: {
        id: 1,
        guru_kelas_id: 10,
        judul: 'Tugas 1: Persamaan Linear',
        deskripsi: 'Kerjakan di buku latihan.',
        tanggal_terbit: '2026-08-12 08:00',
        deadline: '2026-08-19 23:59',
        jenis_pengumpulan: 'teks' as const,
        file_name: 'soal.pdf',
        file_size: 2048,
    },
};

describe('guru/Tugas/Index', () => {
    it('menampilkan daftar tugas dengan ringkasan pengumpulan', () => {
        render(Index, { props: baseProps });

        expect(
            screen.getByText('Tugas 1: Persamaan Linear'),
        ).toBeInTheDocument();
        expect(screen.getByText('3/3')).toBeInTheDocument();
        expect(screen.getByText('Lengkap')).toBeInTheDocument();
        expect(screen.getByText('19 Agu 2026 23:59')).toBeInTheDocument();
    });

    it('tombol Buat Tugas membuka modal dan memvalidasi kelas kosong', async () => {
        render(Index, { props: baseProps });

        await fireEvent.click(
            screen.getByRole('button', { name: /Buat Tugas/ }),
        );

        const modal = withinModal('Buat Tugas');
        await fireEvent.click(
            modal.getByRole('button', { name: /Terbitkan Tugas/ }),
        );

        const form = useForm.mock.results[0]?.value;
        expect(form).toBeDefined();
        expect(form.setError).toHaveBeenCalledWith(
            'guru_kelas_id',
            'Pilih kelas & mata pelajaran dulu.',
        );
        expect(form.post).not.toHaveBeenCalled();
    });

    it('setelah buat tugas berhasil, modal tertutup dan form direset', async () => {
        render(Index, { props: baseProps });

        await fireEvent.click(
            screen.getByRole('button', { name: /Buat Tugas/ }),
        );

        const modal = withinModal('Buat Tugas');
        const form = useForm.mock.results[0]?.value;
        form.guru_kelas_id = 10;
        await fireEvent.click(
            modal.getByRole('button', { name: /Terbitkan Tugas/ }),
        );

        expect(form.post).toHaveBeenCalledTimes(1);
        expect(form.reset).toHaveBeenCalled();
        expect(
            document.querySelector('.modal-content'),
        ).not.toBeInTheDocument();
    });

    it('tombol edit memuat data tugas lewat partial reload', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit tugas'));

        expect(router.get).toHaveBeenCalledWith(
            '/app/guru/tugas/1/edit',
            {},
            expect.objectContaining({ only: ['editTugas'] }),
        );

        await rerender(editTugasProps);
        await tick();

        const modal = withinModal('Edit Tugas');
        expect(
            modal.getByLabelText('Judul Tugas'),
        ).toBeInTheDocument();
        expect(
            modal.getByText(/Berkas saat ini: soal\.pdf \(2\.0 KB\)/),
        ).toBeInTheDocument();

        const editForm = useForm.mock.results[1]?.value;
        expect(editForm.judul).toBe('Tugas 1: Persamaan Linear');
        expect(editForm.guru_kelas_id).toBe(10);
        expect(editForm.deadline).toBe('2026-08-19 23:59');
        expect(editForm.jenis_pengumpulan).toBe('teks');
    });

    it('menyimpan perubahan mengirim PUT ke endpoint update', async () => {
        const { rerender } = render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Edit tugas'));
        await rerender(editTugasProps);
        await tick();

        const modal = withinModal('Edit Tugas');
        const judulInput = modal.getByLabelText('Judul Tugas');
        await fireEvent.input(judulInput, {
            target: { value: 'Tugas 1 (Revisi)' },
        });
        await fireEvent.click(
            modal.getByRole('button', { name: /Simpan Perubahan/ }),
        );

        const editForm = useForm.mock.results[1]?.value;
        expect(editForm.put).toHaveBeenCalledTimes(1);
        expect(editForm.put.mock.calls[0][0]).toBe('/app/guru/tugas/1');
        expect(editForm.calls[0].payload).toEqual(
            expect.objectContaining({
                guru_kelas_id: 10,
                judul: 'Tugas 1 (Revisi)',
                deskripsi: 'Kerjakan di buku latihan.',
                jenis_pengumpulan: 'teks',
                file: null,
            }),
        );
        expect(editForm.reset).toHaveBeenCalled();
        expect(
            screen.queryByText('Edit Tugas'),
        ).not.toBeInTheDocument();
    });

    it('tombol hapus meminta konfirmasi lalu mengirim DELETE', async () => {
        render(Index, { props: baseProps });

        await fireEvent.click(screen.getByTitle('Hapus tugas'));

        expect(router.delete).toHaveBeenCalledWith('/app/guru/tugas/1', {
            preserveScroll: true,
        });
    });
});