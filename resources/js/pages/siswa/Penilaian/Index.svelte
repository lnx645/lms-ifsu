<script lang="ts">
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';

    type NilaiItem = {
        nama: string;
        tipe: string | null;
        sumber: 'manual' | 'tugas';
        nilai: number | null;
        nilai_maks: number | null;
    };

    type MatpelItem = {
        id: number;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        nilai: NilaiItem[];
    };

    let { matpel }: { matpel: MatpelItem[] } = $props();

    const totalNilai = $derived(
        matpel.reduce(
            (total, m) =>
                total +
                m.nilai.filter((n) => n.nilai !== null).length,
            0,
        ),
    );

    const rataRata = $derived(
        (() => {
            const semua: number[] = [];
            for (const m of matpel) {
                for (const n of m.nilai) {
                    if (n.nilai !== null && n.nilai_maks) {
                        semua.push(n.nilai / n.nilai_maks);
                    }
                }
            }
            if (semua.length === 0) return null;
            return (
                (semua.reduce((a, b) => a + b, 0) / semua.length) *
                100
            );
        })(),
    );
    const matpelBerdinilai = $derived(
        matpel.filter((m) =>
            m.nilai.some((n) => n.nilai !== null),
        ).length,
    );

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
    <!-- Hero dengan logo sekolah + judul -->
    <div class="nilai-hero mb-4">
        <div class="nilai-hero__inner">
            <img class="nilai-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="nilai-hero__text">
                <span class="nilai-hero__eyebrow">Perkembangan</span>
                <h1 class="nilai-hero__title">Nilai Saya</h1>
                <p class="nilai-hero__subtitle">
                    Pantau nilai tugas dan penilaianmu dari semua mata pelajaran.
                </p>
            </div>
        </div>
    </div>

    {#if matpel.length > 0}
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--primary h-100">
                    <div class="stat-icon"><i class="bi bi-journal-bookmark"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{matpelBerdinilai}</div>
                        <div class="stat-label">Matpel Berdinilai</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--info h-100">
                    <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{totalNilai}</div>
                        <div class="stat-label">Total Nilai Tercatat</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-card stat-card--success h-100">
                    <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">
                            {rataRata !== null ? `${Math.round(rataRata)}` : '—'}
                        </div>
                        <div class="stat-label">Rata-rata</div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {#if matpel.length === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-clipboard-x display-5 d-block mb-2"></i>
                <div>Kamu belum terdaftar di kelas mana pun.</div>
            </CardBody>
        </Card>
    {:else if totalNilai === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-journal-x display-5 d-block mb-2"></i>
                <div>Belum ada nilai yang tercatat. Sabar ya, guru masih menilai.</div>
            </CardBody>
        </Card>
    {:else}
        <div class="row g-3">
            {#each matpel as m (m.id)}
                {#if m.nilai.length > 0}
                    <div class="col-12 col-xl-6">
                        <Card class="border rounded-1 shadow-none h-100">
                            <CardBody class="p-3">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-3 min-w-0">
                                        <div class="nilai-avatar nilai-avatar--{warnaMapel(m.matpel ?? '')}">
                                            {inisialMapel(m.matpel ?? '')}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{m.matpel ?? 'Matpel'}</div>
                                            <div class="small text-muted">
                                                <i class="bi bi-people me-1"></i>{m.kelas ?? 'Kelas'}
                                                <span class="mx-1">·</span>
                                                <i class="bi bi-person me-1"></i>{m.guru ?? 'Guru'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Sumber</th>
                                                <th class="text-end">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {#each m.nilai as n (n.nama + n.sumber)}
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{n.nama}</div>
                                                        {#if n.tipe && n.tipe !== 'tugas'}
                                                            <div class="text-muted small">{n.tipe}</div>
                                                        {/if}
                                                    </td>
                                                    <td>
                                                        {#if n.sumber === 'tugas'}
                                                            <Badge color="info" pill>Dari Tugas</Badge>
                                                        {:else}
                                                            <Badge color="light" pill>Manual</Badge>
                                                        {/if}
                                                    </td>
                                                    <td class="text-end text-nowrap">
                                                        {#if n.nilai !== null}
                                                            <span class="fw-semibold text-success">
                                                                <i class="bi bi-check2-circle me-1"></i>
                                                                {n.nilai}/{n.nilai_maks ?? '—'}
                                                            </span>
                                                        {:else}
                                                            <span class="text-muted small">
                                                                <i class="bi bi-hourglass me-1"></i>Belum dinilai
                                                            </span>
                                                        {/if}
                                                    </td>
                                                </tr>
                                            {/each}
                                        </tbody>
                                    </table>
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                {/if}
            {/each}
        </div>
    {/if}
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .nilai-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #3d5afe 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.15);
    }

    .nilai-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .nilai-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .nilai-hero__text {
        min-width: 0;
    }

    .nilai-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .nilai-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
    }

    .nilai-hero__subtitle {
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
    .stat-card--info {
        background: linear-gradient(135deg, #0284c7, #0369a1);
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

    /* ---------- Avatar mapel ---------- */
    .nilai-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.8rem;
        font-weight: 700;
        font-size: 1.05rem;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .nilai-avatar--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .nilai-avatar--secondary {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    .nilai-avatar--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .nilai-avatar--info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .nilai-avatar--warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .nilai-avatar--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    @media (max-width: 575.98px) {
        .nilai-hero {
            padding: 1.1rem 1.1rem;
        }
        .nilai-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .nilai-hero__title {
            font-size: 1.25rem;
        }
        .stat-value {
            font-size: 1.3rem;
        }
    }
</style>