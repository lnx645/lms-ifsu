<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import {
        PENGGUMPULAN_INFO,
        STATUS_TUGAS_INFO,
        sisaWaktu,
    } from '@/lib/tugas';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';

    type TugasItem = {
        id: number;
        judul: string;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        tanggal_terbit: string | null;
        deadline: string | null;
        deadline_at: string | null;
        jenis_pengumpulan: keyof typeof PENGGUMPULAN_INFO;
        file_name: string | null;
        poin: number;
        nilai: number | null;
        status: keyof typeof STATUS_TUGAS_INFO;
        submitted_at: string | null;
    };

    let { tugases }: { tugases: TugasItem[] } = $props();

    const totalTugas = $derived(tugases.length);
    const belumDikerjakan = $derived(
        tugases.filter((t) => t.status === 'belum').length,
    );
    const dikumpulkan = $derived(
        tugases.filter((t) => t.status !== 'belum').length,
    );
</script>

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + judul -->
    <div class="tugas-hero mb-4">
        <div class="tugas-hero__inner">
            <img class="tugas-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="tugas-hero__text">
                <span class="tugas-hero__eyebrow">Pekerjaan Rumah</span>
                <h1 class="tugas-hero__title">Tugas Saya</h1>
                <p class="tugas-hero__subtitle">
                    Kerjakan dan kumpulkan tugas sebelum batas waktu.
                </p>
            </div>
        </div>
    </div>

    {#if tugases.length > 0}
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--primary h-100">
                    <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{totalTugas}</div>
                        <div class="stat-label">Total Tugas</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--warning h-100">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{belumDikerjakan}</div>
                        <div class="stat-label">Belum Dikerjakan</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--success h-100">
                    <div class="stat-icon"><i class="bi bi-check2-all"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{dikumpulkan}</div>
                        <div class="stat-label">Sudah Dikumpulkan</div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {#if tugases.length === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-clipboard-check display-5 d-block mb-2"></i>
                <div>Belum ada tugas untukmu. Santai dulu!</div>
            </CardBody>
        </Card>
    {:else}
        <div class="row g-3">
            {#each tugases as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <a
                        use:inertia
                        href={TugasController.show({ tugas: item.id }).url}
                        class="text-decoration-none"
                    >
                        <Card class="border rounded-1 shadow-none tugas-card h-100">
                            <CardBody class="p-3 d-flex flex-column">
                                <div class="d-flex align-items-start gap-3 mb-2">
                                    <div class="tugas-avatar tugas-avatar--{STATUS_TUGAS_INFO[item.status].color}">
                                        <i class={`bi ${STATUS_TUGAS_INFO[item.status].icon}`}></i>
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <span class="badge text-bg-light border text-body small fw-normal tugas-matpel">
                                                <i class="bi bi-journal-bookmark me-1"></i
                                                ><span class="text-truncate">{item.matpel ?? 'Matpel'}</span>
                                            </span>
                                            <Badge
                                                color={STATUS_TUGAS_INFO[item.status].color}
                                                pill
                                                class="flex-shrink-0"
                                            >
                                                {STATUS_TUGAS_INFO[item.status].label}
                                            </Badge>
                                        </div>
                                        <div class="fw-semibold text-body mb-0 mt-1 text-truncate">{item.judul}</div>
                                    </div>
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-people me-1"></i>{item.kelas ?? 'Kelas'}
                                    <span class="mx-1">·</span>
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class={`bi ${PENGGUMPULAN_INFO[item.jenis_pengumpulan].icon} me-1`}></i>
                                    Kumpul: {PENGGUMPULAN_INFO[item.jenis_pengumpulan].label}
                                </div>
                                <div class="mt-auto d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">
                                        <i class="bi bi-hourglass-split me-1"></i>{item.deadline ?? '—'}
                                    </span>
                                    {#if item.nilai !== null}
                                        <span class="text-success fw-semibold text-nowrap">
                                            <i class="bi bi-check2-circle me-1"></i>Nilai: {item.nilai}/{item.poin}
                                        </span>
                                    {:else if item.status === 'belum' && sisaWaktu(item.deadline_at)}
                                        <span class="text-primary fw-semibold">{sisaWaktu(item.deadline_at)}</span>
                                    {:else if item.submitted_at}
                                        <span class="text-success">
                                            <i class="bi bi-check2 me-1"></i>{item.submitted_at}
                                        </span>
                                    {/if}
                                </div>
                            </CardBody>
                        </Card>
                    </a>
                </div>
            {/each}
        </div>
    {/if}
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .tugas-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #3d5afe 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.15);
    }

    .tugas-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .tugas-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .tugas-hero__text {
        min-width: 0;
    }

    .tugas-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .tugas-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
    }

    .tugas-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
    }

    /* ---------- Stat cards ---------- */
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
    .stat-card--warning {
        background: linear-gradient(135deg, #ea580c, #c2410c);
    }
    .stat-card--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
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

    /* ---------- Kartu tugas ---------- */
    .tugas-matpel {
        min-width: 0;
        overflow: hidden;
    }

    .tugas-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.8rem;
        font-size: 1.2rem;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .tugas-avatar--secondary {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    .tugas-avatar--info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .tugas-avatar--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .tugas-avatar--warning {
        background: linear-gradient(135deg, #ea580c, #c2410c);
    }
    .tugas-avatar--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    .tugas-avatar--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .tugas-card {
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .tugas-card:hover {
        border-color: var(--bs-primary-border-subtle);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    }

    @media (max-width: 575.98px) {
        .tugas-hero {
            padding: 1.1rem 1.1rem;
        }
        .tugas-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .tugas-hero__title {
            font-size: 1.25rem;
        }
        .stat-value {
            font-size: 1.3rem;
        }
    }
</style>