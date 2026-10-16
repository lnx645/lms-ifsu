<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import SiswaMateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';

    type KelasPenugasan = {
        guru_kelas_id: number;
        nama: string;
    };

    type LastMateri = {
        judul: string;
        created_at: string;
        is_baru: boolean;
    };

    type MatpelItem = {
        id: number;
        name: string;
        description: string | null;
        kelas?: KelasPenugasan[];
        guru?: string | null;
        total_materi: number;
        last_materi?: LastMateri | null;
    };

    let {
        role = 'guru',
        tahunAjaran = null,
        kelas = null,
        matpels = [],
    }: {
        role: string;
        tahunAjaran: string | null;
        kelas: string | null;
        matpels: MatpelItem[];
    } = $props();

    const isGuru = role === 'guru';

    const guruMateriUrl = GuruMateriController.index().url;
    const siswaMateriUrl = SiswaMateriController.index().url;

    const totalMateri = $derived(
        matpels.reduce((jumlah, item) => jumlah + item.total_materi, 0),
    );
    const mapelDenganMateri = $derived(
        matpels.filter((item) => item.total_materi > 0).length,
    );
    const mapelKosong = $derived(
        matpels.filter((item) => item.total_materi === 0).length,
    );

    // Palet warna tema Bootstrap untuk avatar mapel (deterministik per nama).
    const PALETBIRU = [
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
        return PALETBIRU[Math.abs(hash) % PALETBIRU.length];
    }

    function inisialMapel(nama: string): string {
        return nama
            .split(/\s+/)
            .slice(0, 2)
            .map((w) => w[0] ?? '')
            .join('')
            .toUpperCase();
    }

    function stripHtml(html: string | null): string {
        if (!html) return '';
        return html
            .replace(/&nbsp;/gi, ' ')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#0*39;/g, "'")
            .replace(/&amp;/g, '&')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }
</script>

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + judul halaman -->
    <div class="matpel-hero mb-4">
        <div class="matpel-hero__inner">
            <img
                class="matpel-hero__logo"
                src="/logo.webp"
                alt="Logo sekolah"
            />
            <div class="matpel-hero__text">
                <h1 class="matpel-hero__title">
                    {isGuru ? 'Matpel Saya' : 'Mata Pelajaran'}
                </h1>
                <p class="matpel-hero__subtitle">
                    {isGuru
                        ? `Mata pelajaran yang kamu ajar pada tahun ajaran ${tahunAjaran ?? 'aktif'}.`
                        : kelas
                          ? `Mata pelajaran kelas ${kelas} pada tahun ajaran ${tahunAjaran ?? 'aktif'}.`
                          : `Mata pelajaranmu pada tahun ajaran ${tahunAjaran ?? 'aktif'}.`}
                </p>
            </div>
        </div>
    </div>

    {#if matpels.length > 0}
        <!-- Baris statistik -->
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--primary h-100">
                    <div class="stat-icon">
                        <i class="bi bi-journal-bookmark"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{matpels.length}</div>
                        <div class="stat-label">Mata Pelajaran</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--info h-100">
                    <div class="stat-icon">
                        <i class="bi bi-files"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{totalMateri}</div>
                        <div class="stat-label">Total Materi</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--success h-100">
                    <div class="stat-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{mapelDenganMateri}</div>
                        <div class="stat-label">Punya Materi</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--warning h-100">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{mapelKosong}</div>
                        <div class="stat-label">Belum Ada Materi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar mata pelajaran -->
        <div class="row g-3">
            {#each matpels as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-sm h-100 matpel-card">
                        <CardBody class="d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div
                                    class="matpel-avatar matpel-avatar--{warnaMapel(item.name)} flex-shrink-0"
                                >
                                    {inisialMapel(item.name)}
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h3 class="h6 fw-semibold mb-0 text-truncate text-body-emphasis">
                                            {item.name}
                                        </h3>
                                        {#if !isGuru && item.last_materi?.is_baru}
                                            <Badge color="danger" class="flex-shrink-0">Baru</Badge>
                                        {/if}
                                    </div>
                                    {#if !isGuru}
                                        <div class="text-muted small text-truncate">
                                            <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru pengampu'}
                                        </div>
                                    {/if}
                                </div>
                            </div>

                            {#if item.description}
                                <div
                                    class="text-muted small matpel-deskripsi-snippet mb-3"
                                    title={stripHtml(item.description)}
                                >
                                    {stripHtml(item.description)}
                                </div>
                            {/if}

                            {#if !isGuru && item.last_materi}
                                <div class="last-materi mb-3">
                                    <div class="last-materi-label">
                                        <i class="bi bi-clock-history me-1"></i>Materi terbaru
                                    </div>
                                    <div class="last-materi-judul text-truncate">
                                        {item.last_materi.judul}
                                    </div>
                                    <div class="last-materi-date">
                                        {item.last_materi.created_at}
                                    </div>
                                </div>
                            {:else if isGuru && item.kelas?.length}
                                <div class="mb-3">
                                    <div class="text-muted small fw-semibold mb-1">Kelas yang diajar</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        {#each item.kelas as k (k.guru_kelas_id)}
                                            <a
                                                use:inertia
                                                href={`${guruMateriUrl}?guru_kelas_id=${k.guru_kelas_id}`}
                                                class="badge text-bg-light border text-decoration-none text-body"
                                            >
                                                <i class="bi bi-people me-1"></i>{k.nama}
                                            </a>
                                        {/each}
                                    </div>
                                </div>
                            {:else if isGuru}
                                <div class="flex-grow-1"></div>
                            {/if}

                            <div class="mt-auto d-flex align-items-center justify-content-between gap-2 pt-2 border-top">
                                <span class="text-muted small">
                                    <i class="bi bi-files me-1"></i>
                                    {item.total_materi} materi
                                </span>
                                {#if isGuru}
                                    <a use:inertia href={guruMateriUrl} class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-files me-1"></i>Kelola Materi
                                    </a>
                                {:else}
                                    <a
                                        use:inertia
                                        href={`${siswaMateriUrl}?matpel=${item.id}`}
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="bi bi-eye me-1"></i>Lihat Materi
                                    </a>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-journal-bookmark" style="font-size: 3rem"></i>
                    <p class="mt-3 mb-0">
                        {isGuru
                            ? 'Kamu belum memiliki penugasan kelas aktif pada tahun ajaran ini. Hubungi admin untuk menambahkan penugasan.'
                            : 'Belum ada mata pelajaran untuk kelasmu pada tahun ajaran ini.'}
                    </p>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .matpel-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.4rem 1.5rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #3d5afe 100%);
        color: #fff;
        box-shadow: 0 0.5rem 1.25rem rgba(13, 110, 253, 0.25);
    }

    .matpel-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .matpel-hero__logo {
        width: 4.25rem;
        height: 4.25rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .matpel-hero__title {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 0.15rem;
        line-height: 1.15;
        color: #fff;
    }

    .matpel-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
        max-width: 56ch;
    }

    /* ---------- Stat cards (warna solid & jelas) ---------- */
    .stat-card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.9rem 1rem;
        border-radius: 0.9rem;
        color: #fff;
        box-shadow: 0 0.35rem 0.9rem rgba(0, 0, 0, 0.12);
    }

    .stat-card--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .stat-card--info {
        background: linear-gradient(135deg, #0284c7, #0369a1);
    }
    .stat-card--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .stat-card--warning {
        background: linear-gradient(135deg, #ea580c, #c2410c);
    }

    .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.8rem;
        font-size: 1.3rem;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.2);
    }

    .stat-body {
        min-width: 0;
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
    }

    .stat-label {
        font-size: 0.76rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
    }

    .matpel-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .matpel-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }

    .matpel-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 0.85rem;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .matpel-avatar--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .matpel-avatar--secondary {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    .matpel-avatar--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .matpel-avatar--info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .matpel-avatar--warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .matpel-avatar--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .matpel-deskripsi-snippet {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .last-materi {
        background: var(--bs-secondary-bg, var(--bs-body-bg));
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        padding: 0.6rem 0.75rem;
    }

    .last-materi-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 600;
        color: var(--bs-secondary-color);
        margin-bottom: 0.2rem;
    }

    .last-materi-judul {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--bs-body-emphasis, var(--bs-body-color));
    }

    .last-materi-date {
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
        margin-top: 0.1rem;
    }

    @media (max-width: 575.98px) {
        .matpel-hero {
            padding: 1.1rem 1.1rem;
        }
        .matpel-hero__logo {
            width: 3.4rem;
            height: 3.4rem;
        }
        .matpel-hero__title {
            font-size: 1.2rem;
        }
        .stat-value {
            font-size: 1.3rem;
        }
        .stat-icon {
            width: 2.4rem;
            height: 2.4rem;
            font-size: 1.05rem;
        }
    }
</style>
