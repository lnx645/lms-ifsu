<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Card, CardBody, CardTitle } from '@sveltestrap/sveltestrap';
    import IsRole from '@/components/IsRole.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import MatpelGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import GuruTugasController from '@/actions/App/Http/Controllers/Guru/TugasController';
    import GuruUjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import GuruPenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';
    import SiswaPenilaianController from '@/actions/App/Http/Controllers/Siswa/PenilaianController';

    type MateriBaru = {
        id: number;
        judul: string;
        matpel: string | null;
        guru: string | null;
        dibuat_pada: string;
    };

    type TugasBelum = {
        id: number;
        judul: string;
        matpel: string | null;
        deadline: string | null;
    };

    type RingkasanMatpel = {
        id: number | null;
        matpel: string;
        total: number;
    };

    type Kutipan = {
        teks: string;
        penulis: string;
    };

    type AksiCepat = {
        href: string;
        label: string;
        deskripsi: string;
        ikon: string;
        varian: 'primary' | 'success' | 'warning' | 'info' | 'violet' | 'rose';
        external?: boolean;
    };

    let {
        nama = null,
        kelas = null,
        tahunAjaran = null,
        kutipan = null,
        materiTerbaru = [],
        ringkasan = [],
        tugasBelum = [],
    }: {
        nama: string | null;
        kelas: string | null;
        tahunAjaran: string | null;
        kutipan: Kutipan | null;
        materiTerbaru: MateriBaru[];
        ringkasan: RingkasanMatpel[];
        tugasBelum: TugasBelum[];
    } = $props();

    const materiUrl = MateriController.index().url;

    const totalMateri = ringkasan.reduce(
        (jumlah, item) => jumlah + item.total,
        0,
    );

    const tanggalHariIni = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const sapaanWaktu = (() => {
        const jam = new Date().getHours();
        if (jam < 11) return 'Selamat pagi';
        if (jam < 15) return 'Selamat siang';
        if (jam < 19) return 'Selamat sore';
        return 'Selamat malam';
    })();

    // Aksi cepat siswa: 4 tujuan utama dalam satu ketukan.
    const aksiSiswa: AksiCepat[] = [
        {
            href: SiswaUjianController.index().url,
            label: 'Kerjakan Ujian',
            deskripsi: 'Ujian CBT yang tersedia',
            ikon: 'bi-pencil-square',
            varian: 'violet',
        },
        {
            href: TugasController.index().url,
            label: 'Tugas Saya',
            deskripsi: `${tugasBelum.length} menunggu dikerjakan`,
            ikon: 'bi-clipboard-check',
            varian: 'warning',
        },
        {
            href: MateriController.index().url,
            label: 'Belajar Materi',
            deskripsi: `${totalMateri} materi tersedia`,
            ikon: 'bi-book-half',
            varian: 'primary',
        },
        {
            href: SiswaPenilaianController.index().url,
            label: 'Nilai Saya',
            deskripsi: 'Lihat hasil belajarmu',
            ikon: 'bi-award',
            varian: 'success',
        },
        {
            href: '/app/ujian/kartu-saya',
            label: 'Kartu Ujian',
            deskripsi: 'Download kartu peserta',
            ikon: 'bi-person-badge',
            varian: 'info',
            external: true,
        },
    ];

    // Aksi cepat guru: 6 alur kerja utama.
    const aksiGuru: AksiCepat[] = [
        {
            href: GuruUjianController.index().url,
            label: 'Kelola Ujian',
            deskripsi: 'Buat & atur ujian CBT',
            ikon: 'bi-pencil-square',
            varian: 'violet',
        },
        {
            href: '/app/guru/bank-soal/kelompok',
            label: 'Bank Soal',
            deskripsi: 'Koleksi soal siap pakai',
            ikon: 'bi-collection',
            varian: 'info',
        },
        {
            href: GuruTugasController.index().url,
            label: 'Kelola Tugas',
            deskripsi: 'Beri & periksa tugas',
            ikon: 'bi-clipboard-check',
            varian: 'warning',
        },
        {
            href: GuruMateriController.index().url,
            label: 'Kelola Materi',
            deskripsi: 'Unggah bahan ajar',
            ikon: 'bi-journal-plus',
            varian: 'primary',
        },
        {
            href: GuruPenilaianController.index().url,
            label: 'Input Nilai',
            deskripsi: 'Nilai tugas & ujian',
            ikon: 'bi-award',
            varian: 'success',
        },
        {
            href: MatpelGuruController.index().url,
            label: 'Kelas Saya',
            deskripsi: 'Lihat kelas yang diajar',
            ikon: 'bi-easel2',
            varian: 'rose',
        },
    ];

    // Palet warna solid untuk avatar mapel (deterministik per nama).
    const PALET = [
        'primary',
        'success',
        'info',
        'warning',
        'danger',
        'secondary',
    ] as const;

    function warnaMapel(nama: string): string {
        let hash = 0;
        for (let i = 0; i < nama.length; i++) {
            hash = (hash << 5) - hash + nama.charCodeAt(i);
            hash |= 0;
        }
        return PALET[Math.abs(hash) % PALET.length];
    }

    function inisialMapel(nama: string): string {
        return nama
            .split(/\s+/)
            .slice(0, 2)
            .map((w) => w[0] ?? '')
            .join('')
            .toUpperCase();
    }
</script>

<div class="container-fluid px-0">
    <!-- Sapaan: bersih -->
    <section class="dash-sapaan mb-4">
        <div class="dash-sapaan__tanggal">
            <i class="bi bi-calendar3 me-1"></i>{tanggalHariIni}
        </div>
        <h1 class="dash-sapaan__judul">
            {sapaanWaktu}{nama ? `, ${nama}` : ''}!
        </h1>
        <p class="dash-sapaan__sub">
            {#if kelas && tahunAjaran}
                Kelas {kelas} · {tahunAjaran}
            {:else if tahunAjaran}
                Tahun Ajaran {tahunAjaran}
            {:else}
                Selamat datang di portal pembelajaran sekolah.
            {/if}
        </p>
    </section>

    <!-- ══════════ AKSI CEPAT SISWA ══════════ -->
    <IsRole role="siswa">
        <section class="mb-4" aria-label="Aksi cepat">
            <h2 class="dash-judul">Mau ngapain hari ini?</h2>
            <div class="row g-3">
                {#each aksiSiswa as aksi (aksi.label)}
                    <div class="col-6 col-md-3">
                        {#if aksi.external}
                            <a href={aksi.href} target="_blank" class="dash-aksi h-100">
                                <span class="dash-aksi__ikon"><i class="bi {aksi.ikon}"></i></span>
                                <span>
                                    <span class="dash-aksi__label">{aksi.label}</span>
                                    <span class="dash-aksi__deskripsi">{aksi.deskripsi}</span>
                                </span>
                            </a>
                        {:else}
                            <a use:inertia href={aksi.href} class="dash-aksi h-100">
                                <span class="dash-aksi__ikon"><i class="bi {aksi.ikon}"></i></span>
                                <span>
                                    <span class="dash-aksi__label">{aksi.label}</span>
                                    <span class="dash-aksi__deskripsi">{aksi.deskripsi}</span>
                                </span>
                            </a>
                        {/if}
                    </div>
                {/each}
            </div>
        </section>
    </IsRole>

    <!-- ══════════ AKSI CEPAT GURU ══════════ -->
    <IsRole role="guru">
        <section class="mb-4" aria-label="Aksi cepat guru">
            <h2 class="dash-judul">Ruang Kerja Guru</h2>
            <div class="row g-3">
                {#each aksiGuru as aksi (aksi.label)}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a
                            use:inertia
                            href={aksi.href}
                            class="dash-aksi h-100"
                        >
                            <span class="dash-aksi__ikon">
                                <i class="bi {aksi.ikon}"></i>
                            </span>
                            <span>
                                <span class="dash-aksi__label">{aksi.label}</span>
                                <span class="dash-aksi__deskripsi">{aksi.deskripsi}</span>
                            </span>
                        </a>
                    </div>
                {/each}
            </div>
        </section>
    </IsRole>


    <!-- ══════════ TUGAS & MATERI SISWA ══════════ -->
    <IsRole role="siswa">
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <Card class="h-100">
                    <CardBody>
                        <CardTitle tag="h2" class="dash-judul">
                            <i class="bi bi-clipboard-check text-warning me-1"></i>
                            Tugas Belum Dikerjakan
                            {#if tugasBelum.length > 0}
                                <span class="badge bg-warning text-dark ms-1">{tugasBelum.length}</span>
                            {/if}
                        </CardTitle>
                        {#if tugasBelum.length > 0}
                            <div class="list-group list-group-flush mt-2">
                                {#each tugasBelum as item (item.id)}
                                    <a
                                        use:inertia
                                        href={TugasController.show({
                                            tugas: item.id,
                                        }).url}
                                        class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0"
                                    >
                                        <i class="bi bi-hourglass-split text-warning"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-truncate">{item.judul}</div>
                                            <div class="small text-muted">
                                                {item.matpel ?? 'Matpel'}
                                                {#if item.deadline}
                                                    · <i class="bi bi-alarm me-1"></i>{item.deadline}
                                                {/if}
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                {/each}
                            </div>
                        {:else}
                            <div class="alert alert-success mt-2 mb-0 py-2">
                                <i class="bi bi-check2-circle me-1"></i>
                                Semua tugas sudah dikerjakan. Mantap!
                            </div>
                        {/if}
                        <div class="mt-3">
                            <a
                                use:inertia
                                href={TugasController.index().url}
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="bi bi-list-check me-1"></i>Lihat Semua Tugas
                            </a>
                        </div>
                    </CardBody>
                </Card>
            </div>

            <div class="col-12 col-xl-5">
                <Card class="h-100">
                    <CardBody>
                        <CardTitle tag="h2" class="dash-judul">
                            <i class="bi bi-clock-history text-primary me-1"></i>
                            Materi Terbaru
                        </CardTitle>
                        {#if materiTerbaru.length > 0}
                            <div class="list-group list-group-flush mt-2">
                                {#each materiTerbaru as item (item.id)}
                                    <a
                                        use:inertia
                                        href={MateriController.show({
                                            materi: item.id,
                                        }).url}
                                        class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0"
                                    >
                                        <i class="bi bi-file-earmark-richtext text-primary"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-truncate">{item.judul}</div>
                                            <div class="small text-muted">
                                                {item.matpel ?? 'Matpel'} · {item.guru ?? 'Guru'}
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                {/each}
                            </div>
                            <div class="mt-3">
                                <a
                                    use:inertia
                                    href={materiUrl}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-collection me-1"></i>Lihat Semua Materi
                                </a>
                            </div>
                        {:else}
                            <div class="alert alert-light mt-2 mb-0">
                                <i class="bi bi-inbox"></i>
                                <span>Belum ada materi untuk kelasmu.</span>
                            </div>
                        {/if}
                    </CardBody>
                </Card>
            </div>
        </div>
    </IsRole>

</div>

