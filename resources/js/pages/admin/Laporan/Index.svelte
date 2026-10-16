<script lang="ts">
    import { router, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Select from '@/components/Select.svelte';
    import LaporanController from '@/actions/App/Http/Controllers/Admin/LaporanController';

    // ── Types ─────────────────────────────────────────────────────────────────
    type TahunAjaranOption = { value: number; label: string };
    type EntityItem = { key: string; label: string; icon: string; count: number; master?: boolean };

    // ── Props ─────────────────────────────────────────────────────────────────
    let {
        counts,
        tahunAjaranOptions = [],
        selectedTahunAjaran = null,
    }: {
        counts: Record<string, number>;
        tahunAjaranOptions: TahunAjaranOption[];
        selectedTahunAjaran: number | null;
    } = $props();

    // ── Filter state ──────────────────────────────────────────────────────────
    let filterTA: number | null = $state(selectedTahunAjaran);

    // ── Entities config ──────────────────────────────────────────────────────
    const entities: EntityItem[] = [
        { key: 'jurusan', label: 'Jurusan', icon: 'bi-diagram-3-fill', count: 0, master: true },
        { key: 'matpel', label: 'Mata Pelajaran', icon: 'bi-book-half', count: 0, master: true },
        { key: 'tahunAjaran', label: 'Tahun Ajaran', icon: 'bi-calendar', count: 0, master: true },
        { key: 'kelas', label: 'Kelas', icon: 'bi-collection-fill', count: 0, master: false },
        { key: 'guru', label: 'Guru', icon: 'bi-people-fill', count: 0, master: true },
        { key: 'siswa', label: 'Siswa', icon: 'bi-mortarboard-fill', count: 0, master: false },
        { key: 'penugasan', label: 'Penugasan Guru-Kelas', icon: 'bi-person-workspace', count: 0, master: false },
        { key: 'materi', label: 'Materi', icon: 'bi-file-earmark-richtext', count: 0, master: false },
        { key: 'tugas', label: 'Tugas', icon: 'bi-list-check', count: 0, master: false },
        { key: 'pengumpulan', label: 'Pengumpulan Tugas', icon: 'bi-inbox', count: 0, master: false },
        { key: 'penilaian', label: 'Penilaian', icon: 'bi-card-checklist', count: 0, master: true },
        { key: 'detailNilai', label: 'Detail Nilai', icon: 'bi-clipboard-data', count: 0, master: false },
        { key: 'siswaKelas', label: 'Riwayat Kelas Siswa', icon: 'bi-arrow-left-right', count: 0, master: false },
        { key: 'users', label: 'Akun Pengguna', icon: 'bi-person-badge', count: 0, master: true },
    ].map((e) => ({ ...e, count: counts[e.key] ?? 0 }));

    const total = $derived(entities.reduce((sum, e) => sum + e.count, 0));

    const taLabel = $derived(
        tahunAjaranOptions.find((t) => t.value === filterTA)?.label ?? null,
    );

    // ── Helpers ───────────────────────────────────────────────────────────────
    function selectValue<T>(v: unknown, fallback: T): T {
        return (v as { value: T } | null)?.value ?? fallback;
    }

    function buildExportUrl(format: 'xlsx' | 'pdf'): string {
        const base = format === 'xlsx'
            ? LaporanController.exportXlsx().url
            : LaporanController.exportPdf().url;
        const params = new URLSearchParams();
        if (filterTA) {
            params.set('tahun_ajaran_id', String(filterTA));
        }
        return params.toString() ? `${base}?${params}` : base;
    }

    function onFilterTA(v: unknown) {
        filterTA = selectValue(v, null as null);
        const url = (usePage().url as string).split('?')[0];
        router.get(url, filterTA ? { tahun_ajaran_id: filterTA } : {}, {
            preserveState: true,
            replace: true,
            only: ['counts', 'selectedTahunAjaran'],
        });
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Laporan Data"
        subtitle="Ekspor seluruh data aplikasi ke XLSX atau PDF."
    />

    <!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
    {#if filterTA && taLabel}
        <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size:0.85rem">
            <i class="bi bi-funnel-fill"></i>
            Filter aktif: <strong>{taLabel}</strong>
            <span class="text-muted ms-1">— data Siswa, Kelas, Penugasan, Materi, Tugas, Pengumpulan, Detail Nilai, Riwayat Kelas menyesuaikan.</span>
            <button class="btn btn-sm btn-outline-info ms-auto" onclick={() => onFilterTA(null)}>
                <i class="bi bi-x-lg"></i> Reset
            </button>
        </div>
    {/if}

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div style="min-width: 220px">
                    <Select
                        id="f-ta"
                        items={tahunAjaranOptions}
                        value={filterTA}
                        placeholder="Semua Tahun Ajaran"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={onFilterTA}
                    />
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Filter TA diterapkan pada laporan transaksi. Data master (Jurusan, Matpel, dll) selalu keseluruhan.
                </div>
            </div>
        </CardBody>
    </Card>

    <!-- ── Export Cards ─────────────────────────────────────────────────── -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <Card class="border-success border rounded-1 shadow-none h-100">
                <CardBody class="p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-excel text-success" style="font-size:3rem;line-height:1"></i>
                    </div>
                    <div class="fw-semibold mb-1">Export XLSX</div>
                    <div class="text-muted small mb-3">
                        {entities.length} sheet · {total.toLocaleString('id-ID')} baris data
                        {#if filterTA}<br /><Badge color="info" class="mt-1">{taLabel}</Badge>{/if}
                    </div>
                    <a class="btn btn-success w-100" href={buildExportUrl('xlsx')}>
                        <i class="bi bi-download me-1"></i>Unduh XLSX
                    </a>
                </CardBody>
            </Card>
        </div>
        <div class="col-md-6">
            <Card class="border-danger border rounded-1 shadow-none h-100">
                <CardBody class="p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem;line-height:1"></i>
                    </div>
                    <div class="fw-semibold mb-1">Export PDF</div>
                    <div class="text-muted small mb-3">
                        {entities.length} sheet · {total.toLocaleString('id-ID')} baris data
                        {#if filterTA}<br /><Badge color="info" class="mt-1">{taLabel}</Badge>{/if}
                    </div>
                    <a class="btn btn-danger w-100" href={buildExportUrl('pdf')}>
                        <i class="bi bi-download me-1"></i>Unduh PDF
                    </a>
                </CardBody>
            </Card>
        </div>
    </div>

    <!-- ── Stats Grid ─────────────────────────────────────────────────────── -->
    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="row g-2">
                {#each entities as e (e.key)}
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="border rounded-1 p-2 h-100">
                            <div class="d-flex align-items-start gap-2">
                                <i class={`bi ${e.icon} text-primary flex-shrink-0`} style="font-size:1.2rem;margin-top:1px"></i>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small text-muted text-truncate">{e.label}</div>
                                    <div class="fw-bold" style="font-size:1.1rem;line-height:1.2">
                                        {e.count.toLocaleString('id-ID')}
                                        {#if filterTA}
                                            <span class="badge {e.master ? 'bg-secondary' : 'bg-info'}" style="font-size:0.6rem;vertical-align:middle">
                                                {e.master ? 'master' : 'filter'}
                                            </span>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>

            <!-- Total -->
            <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total Baris Data</span>
                <span class="fw-bold fs-5">{total.toLocaleString('id-ID')}</span>
            </div>
        </CardBody>
    </Card>
</div>
