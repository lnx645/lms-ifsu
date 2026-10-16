import { useForm } from '@inertiajs/svelte';
import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import Detail from '@/pages/siswa/Tugas/Detail.svelte';

// Deadline harus selalu di masa depan relatif terhadap waktu test dijalankan,
// kalau tidak komponen menganggap batas waktu lewat dan menyembunyikan form.
const deadlineAt = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString();

const baseProps = {
    tugas: {
        id: 1,
        judul: 'Tugas 1: Persamaan Linear',
        deskripsi: 'Kerjakan di buku latihan dengan teliti.',
        kelas: 'X-RPL-1',
        matpel: 'Matematika',
        guru: 'Budi Santoso',
        tanggal_terbit: '12 Agu 2026 08:00',
        deadline: '19 Agu 2026 23:59',
        deadline_at: deadlineAt,
        jenis_pengumpulan: 'file' as const,
        file_name: null,
        file_size: 0,
        mime_type: null,
        status: 'belum' as const,
    },
    pengumpulan: null,
};

describe('siswa/Tugas/Detail', () => {
    it('menampilkan detail tugas, guru, dan batas waktu', () => {
        render(Detail, { props: baseProps });

        expect(
            screen.getByText('Tugas 1: Persamaan Linear'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Kerjakan di buku latihan dengan teliti.'),
        ).toBeInTheDocument();
        expect(screen.getByText(/Budi Santoso/)).toBeInTheDocument();
        expect(screen.getByText('19 Agu 2026 23:59')).toBeInTheDocument();
        expect(screen.getByText('Belum Dikerjakan')).toBeInTheDocument();
    });

    it('mengumpulkan tanpa berkas memunculkan pesan error', async () => {
        render(Detail, { props: baseProps });

        await fireEvent.click(
            screen.getByRole('button', { name: /Kumpulkan Tugas/ }),
        );

        const form = useForm.mock.results[0]?.value;
        expect(form).toBeDefined();
        expect(form.post).not.toHaveBeenCalled();
        expect(
            screen.getByText('Pilih berkas jawaban dulu.'),
        ).toBeInTheDocument();
    });

    it('memilih berkas lalu mengirim POST ke endpoint kumpul', async () => {
        render(Detail, { props: baseProps });

        const file = new File(['isi'], 'jawaban.pdf', {
            type: 'application/pdf',
        });
        await fireEvent.change(screen.getByLabelText('Berkas Jawaban'), {
            target: { files: [file] },
        });

        const form = useForm.mock.results[0]?.value;
        expect(form.file).toBe(file);

        await fireEvent.click(
            screen.getByRole('button', { name: /Kumpulkan Tugas/ }),
        );

        expect(form.post).toHaveBeenCalledTimes(1);
        expect(form.post.mock.calls[0][0]).toBe('/app/tugas/1/kumpul');
        expect(form.post.mock.calls[0][1]).toEqual(
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('menolak berkas dengan format tidak didukung', async () => {
        render(Detail, { props: baseProps });

        const file = new File(['x'], 'virus.exe', { type: 'application/x-msdownload' });
        await fireEvent.change(screen.getByLabelText('Berkas Jawaban'), {
            target: { files: [file] },
        });

        const form = useForm.mock.results[0]?.value;
        expect(form.file).toBeNull();
        expect(
            screen.getByText('Format berkas tidak didukung.'),
        ).toBeInTheDocument();
    });

    it('menampilkan status terkumpul dan tombol perbarui jawaban', () => {
        render(Detail, {
            props: {
                ...baseProps,
                tugas: {
                    ...baseProps.tugas,
                    status: 'terkumpul' as const,
                },
                pengumpulan: {
                    id: 5,
                    file_name: 'jawaban.pdf',
                    file_size: 2048,
                    jawaban_teks: null,
                    submitted_at: '14 Agu 2026 09:30',
                    terlambat: false,
                },
            },
        });

        expect(screen.getByText('Terkumpul')).toBeInTheDocument();
        expect(screen.getByText('jawaban.pdf')).toBeInTheDocument();
        expect(screen.getByText('Dikumpulkan 14 Agu 2026 09:30')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Perbarui Jawaban/ }),
        ).toBeInTheDocument();
    });

    it('menampilkan info cara pengumpulan di detail tugas', () => {
        render(Detail, {
            props: {
                ...baseProps,
                tugas: {
                    ...baseProps.tugas,
                    jenis_pengumpulan: 'keduanya' as const,
                },
            },
        });

        expect(
            screen.getByText(/Kumpul: File & Teks/),
        ).toBeInTheDocument();
    });

    it('mode tulis langsung: mengumpulkan teks tanpa berkas', async () => {
        render(Detail, {
            props: {
                ...baseProps,
                tugas: {
                    ...baseProps.tugas,
                    jenis_pengumpulan: 'teks' as const,
                },
            },
        });

        expect(screen.queryByLabelText('Berkas Jawaban')).not.toBeInTheDocument();

        await fireEvent.click(
            screen.getByRole('button', { name: /Kumpulkan Tugas/ }),
        );

        const form = useForm.mock.results[0]?.value;
        expect(form.post).not.toHaveBeenCalled();
        expect(
            screen.getByText('Tulis jawabanmu dulu.'),
        ).toBeInTheDocument();

        await fireEvent.input(screen.getByLabelText('Tulis Jawaban'), {
            target: { value: 'Jawaban esai saya' },
        });
        expect(form.jawaban_teks).toBe('Jawaban esai saya');

        await fireEvent.click(
            screen.getByRole('button', { name: /Kumpulkan Tugas/ }),
        );

        expect(form.post).toHaveBeenCalledTimes(1);
        expect(form.post.mock.calls[0][0]).toBe('/app/tugas/1/kumpul');
    });

    it('mode keduanya: menampilkan teks jawaban yang sudah dikumpulkan', () => {
        render(Detail, {
            props: {
                ...baseProps,
                tugas: {
                    ...baseProps.tugas,
                    jenis_pengumpulan: 'keduanya' as const,
                    status: 'terkumpul' as const,
                },
                pengumpulan: {
                    id: 5,
                    file_name: 'jawaban.pdf',
                    file_size: 2048,
                    jawaban_teks: 'Jawaban esai saya',
                    submitted_at: '14 Agu 2026 09:30',
                    terlambat: false,
                },
            },
        });

        expect(screen.getByText('Jawaban esai saya')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Perbarui Jawaban/ }),
        ).toBeInTheDocument();
    });

    it('memblokir pengumpulan saat batas waktu sudah lewat', () => {
        render(Detail, {
            props: {
                ...baseProps,
                tugas: {
                    ...baseProps.tugas,
                    deadline: '10 Agu 2026 23:59',
                    deadline_at: '2026-08-10T23:59:00+07:00',
                    status: 'tutup' as const,
                },
            },
        });

        expect(
            screen.getByText(/Batas waktu sudah lewat, tugas tidak bisa dikumpulkan lagi\./),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /Kumpulkan Tugas/ }),
        ).not.toBeInTheDocument();
    });
});