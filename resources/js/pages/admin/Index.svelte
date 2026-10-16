<script lang="ts">
    import {
        Badge,
        Card,
        CardBody,
        CardTitle,
        Col,
        Progress,
        Row,
        Table,
    } from '@sveltestrap/sveltestrap';
    import TahunAjaranInfo from '@/components/TahunAjaranInfo.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { router } from '@inertiajs/svelte';

    interface Kpi {
        label: string;
        value: number;
        sub?: string;
        icon: string;
        color: string;
    }

    interface Props {
        stats: {
            guru: number;
            guru_aktif: number;
            siswa: number;
            siswa_dengan_kelas: number;
            kelas: number;
            matpel: number;
            jurusan: number;
            penugasan: number;
            penugasan_aktif: number;
        };
        statsTambahan: {
            siswa_l: number;
            siswa_p: number;
            guru_l: number;
            guru_p: number;
            guru_dengan_penugasan: number;
            kelas_aktif: number;
            guru_dengan_akun: number;
         siswa_dengan_akun: number;
        };
        siswaPerJurusan: { name: string; kode: string | null; siswa: number }[];
        kelasPerJurusan: { name: string; kode: string | null; kelas: number }[];
        siswaPerKelas: { nama: string; siswa: number }[];
        matpelTeratas: { name: string; penugasan: number }[];
        guruTeraktif: { nama: string; penugasan: number }[];
        waliKelas: { dengan_walikelas: number; tanpa_walikelas: number };
        penugasanPerTahun: { name: string; active: boolean; penugasan: number }[];
        tahunAjaranOptions: { id: number; name: string; active: boolean }[];
        tahunAjaranTerpilih: number | null;
    }

    let {
        stats,
        statsTambahan,
        siswaPerJurusan = [],
        kelasPerJurusan = [],
        siswaPerKelas = [],
        matpelTeratas = [],
        guruTeraktif = [],
        waliKelas = { dengan_walikelas: 0, tanpa_walikelas: 0 },
        penugasanPerTahun = [],
        tahunAjaranOptions = [],
        tahunAjaranTerpilih = null,
    }: Props = $props();

    let selectedTahunAjaran = $state(tahunAjaranTerpilih);

    $effect(() => {
        selectedTahunAjaran = tahunAjaranTerpilih;
    });

    function changeTahunAjaran(event: Event) {
        const value = (event.currentTarget as HTMLSelectElement).value;
        selectedTahunAjaran = value ? Number(value) : null;
        const params = value ? { tahun_ajaran: value } : { tahun_ajaran: 'all' };
        const url = (window.location.pathname + window.location.search)
            .split('?')[0];
        router.get(url, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [
                'stats',
                'statsTambahan',
                'siswaPerJurusan',
                'siswaPerKelas',
                'matpelTeratas',
                'guruTeraktif',
                'tahunAjaranTerpilih',
            ],
        });
    }

    const kpiCards = $derived<Kpi[]>([
        {
            label: 'Guru',
            value: stats?.guru ?? 0,
            sub: `${stats?.guru_aktif ?? 0} aktif`,
            icon: 'bi-people-fill',
            color: 'primary',
        },
        {
            label: 'Siswa',
            value: stats?.siswa ?? 0,
            sub: `${stats?.siswa_dengan_kelas ?? 0} sudah berkelas`,
            icon: 'bi-mortarboard-fill',
            color: 'info',
        },
        {
            label: 'Kelas',
            value: stats?.kelas ?? 0,
            sub: `${waliKelas.dengan_walikelas} punya wali kelas`,
            icon: 'bi-collection-fill',
            color: 'success',
        },
        {
            label: 'Mata Pelajaran',
            value: stats?.matpel ?? 0,
            sub: 'kurikulum tersedia',
            icon: 'bi-book-half',
            color: 'warning',
        },
        {
            label: 'Jurusan',
            value: stats?.jurusan ?? 0,
            sub: 'kompetensi keahlian',
            icon: 'bi-diagram-3-fill',
            color: 'secondary',
        },
        {
            label: 'Penugasan',
            value: stats?.penugasan ?? 0,
            sub: `${stats?.penugasan_aktif ?? 0} aktif`,
            icon: 'bi-briefcase-fill',
            color: 'danger',
        },
    ]);

    const totalSiswaJurusan = $derived(
        siswaPerJurusan.reduce((sum, item) => sum + item.siswa, 0) || 1,
    );
    const totalKelasJurusan = $derived(
        kelasPerJurusan.reduce((sum, item) => sum + item.kelas, 0) || 1,
    );
    const totalWalikelas = $derived(
        waliKelas.dengan_walikelas + waliKelas.tanpa_walikelas || 1,
    );
    const maxMatpel = $derived(
        Math.max(1, ...matpelTeratas.map((item) => item.penugasan)),
    );
    const maxGuru = $derived(
        Math.max(1, ...guruTeraktif.map((item) => item.penugasan)),
    );
    const maxSiswaKelas = $derived(
        Math.max(1, ...siswaPerKelas.map((item) => item.siswa)),
    );

    const kpiSecondary = $derived<Kpi[]>([
        {
            label: 'Siswa Laki-laki',
            value: statsTambahan?.siswa_l ?? 0,
            sub: `${statsTambahan?.siswa_p ?? 0} perempuan`,
            icon: 'bi-gender-male',
            color: 'primary',
        },
        {
            label: 'Guru Laki-laki',
            value: statsTambahan?.guru_l ?? 0,
            sub: `${statsTambahan?.guru_p ?? 0} perempuan`,
            icon: 'bi-gender-male',
            color: 'info',
        },
        {
            label: 'Guru Berpenugasan',
            value: statsTambahan?.guru_dengan_penugasan ?? 0,
            sub: `dari ${stats?.guru ?? 0} guru`,
            icon: 'bi-briefcase-fill',
            color: 'success',
        },
        {
            label: 'Kelas Aktif',
            value: statsTambahan?.kelas_aktif ?? 0,
            sub: `dari ${stats?.kelas ?? 0} kelas`,
            icon: 'bi-check-circle-fill',
            color: 'warning',
        },
        {
            label: 'Guru Berakun',
            value: statsTambahan?.guru_dengan_akun ?? 0,
            sub: `dari ${stats?.guru ?? 0} guru`,
            icon: 'bi-person-check-fill',
            color: 'secondary',
        },
        {
            label: 'Siswa Berakun',
            value: statsTambahan?.siswa_dengan_akun ?? 0,
            sub: `dari ${stats?.siswa ?? 0} siswa`,
            icon: 'bi-person-check-fill',
            color: 'danger',
        },
    ]);
</script>

<TahunAjaranInfo class="mb-3" />

<div class="admin-dashboard">
<PageHeader
    title="Dashboard"
    subtitle="Ringkasan statistik data master sekolah"
>
    {#snippet actions()}
        <div class="d-flex align-items-center gap-2">
            <label
                for="filter-tahun-ajaran"
                class="small text-muted mb-0"
                >Tahun Ajaran</label
            >
            <select
                id="filter-tahun-ajaran"
                class="form-select form-select-sm"
                style="min-width: 140px"
                value={selectedTahunAjaran ?? ''}
                onchange={changeTahunAjaran}
            >
                <option value="">Semua</option>
                {#each tahunAjaranOptions as tahun (tahun.id)}
                    <option value={tahun.id}>
                        {tahun.name}
                        {#if tahun.active}
                            (Aktif)
                        {/if}
                    </option>
                {/each}
            </select>
        </div>
    {/snippet}
</PageHeader>

<div class="row g-3 mb-4">
    {#each kpiCards as kpi (kpi.label)}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="admin-kpi admin-kpi--{kpi.color} h-100">
                <div class="admin-kpi__icon">
                    <i class="bi {kpi.icon}"></i>
                </div>
                <div class="admin-kpi__value">{kpi.value}</div>
                <div class="admin-kpi__label">{kpi.label}</div>
                {#if kpi.sub}
                    <div class="admin-kpi__sub">{kpi.sub}</div>
                {/if}
            </div>
        </div>
    {/each}
</div>

<div class="row g-3 mb-4">
    {#each kpiSecondary as kpi (kpi.label)}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="admin-kpi admin-kpi--{kpi.color} h-100">
                <div class="admin-kpi__icon">
                    <i class="bi {kpi.icon}"></i>
                </div>
                <div class="admin-kpi__value">{kpi.value}</div>
                <div class="admin-kpi__label">{kpi.label}</div>
                {#if kpi.sub}
                    <div class="admin-kpi__sub">{kpi.sub}</div>
                {/if}
            </div>
        </div>
    {/each}
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-primary"></i>
                    Sebaran Siswa per Jurusan
                </CardTitle>
                {#if siswaPerJurusan.length}
                    <div class="d-flex flex-column gap-3 mt-3">
                        {#each siswaPerJurusan as item (item.name)}
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">
                                        {item.name}
                                        {#if item.kode}
                                            <Badge color="light" pill>{item.kode}</Badge>
                                        {/if}
                                    </span>
                                    <span>{item.siswa} siswa</span>
                                </div>
                                <Progress
                                    value={(item.siswa / totalSiswaJurusan) * 100}
                                    color="primary"
                                    style="height: 8px"
                                />
                            </div>
                        {/each}
                    </div>
                {:else}
                    <p class="text-muted small mb-0">Belum ada data.</p>
                {/if}
            </CardBody>
        </Card>
    </div>

    <div class="col-12 col-xl-6">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-collection-fill text-success"></i>
                    Kelas per Jurusan
                </CardTitle>
                {#if kelasPerJurusan.length}
                    <div class="d-flex flex-column gap-3 mt-3">
                        {#each kelasPerJurusan as item (item.name)}
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">
                                        {item.name}
                                        {#if item.kode}
                                            <Badge color="light" pill>{item.kode}</Badge>
                                        {/if}
                                    </span>
                                    <span>{item.kelas} kelas</span>
                                </div>
                                <Progress
                                    value={(item.kelas / totalKelasJurusan) * 100}
                                    color="success"
                                    style="height: 8px"
                                />
                            </div>
                        {/each}
                    </div>
                {:else}
                    <p class="text-muted small mb-0">Belum ada data.</p>
                {/if}
            </CardBody>
        </Card>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-4">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill text-secondary"></i>
                    Cakupan Wali Kelas
                </CardTitle>
                <div class="text-center py-3">
                    <div class="display-5 fw-bold text-body">
                        {waliKelas.dengan_walikelas}
                        <span class="fs-6 text-muted fw-normal">/ {totalWalikelas} kelas</span>
                    </div>
                    <div class="text-muted small">memiliki wali kelas</div>
                </div>
                <Progress
                    value={(waliKelas.dengan_walikelas / totalWalikelas) * 100}
                    color="secondary"
                    style="height: 8px"
                />
                {#if waliKelas.tanpa_walikelas > 0}
                    <p class="text-muted small mt-2 mb-0">
                        {waliKelas.tanpa_walikelas} kelas belum memiliki wali kelas.
                    </p>
                {:else}
                    <p class="text-success small mt-2 mb-0">
                        Semua kelas sudah memiliki wali kelas.
                    </p>
                {/if}
            </CardBody>
        </Card>
    </div>

    <div class="col-12 col-xl-4">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-book-half text-warning"></i>
                    Mata Pelajaran Terbanyak Diampu
                </CardTitle>
                {#if matpelTeratas.length}
                    <div class="d-flex flex-column gap-3 mt-3">
                        {#each matpelTeratas as item (item.name)}
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">{item.name}</span>
                                    <Badge color="warning">{item.penugasan}</Badge>
                                </div>
                                <Progress
                                    value={(item.penugasan / maxMatpel) * 100}
                                    color="warning"
                                    style="height: 8px"
                                />
                            </div>
                        {/each}
                    </div>
                {:else}
                    <p class="text-muted small mb-0">Belum ada data.</p>
                {/if}
            </CardBody>
        </Card>
    </div>

    <div class="col-12 col-xl-4">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-danger"></i>
                    Guru Paling Banyak Mengajar
                </CardTitle>
                {#if guruTeraktif.length}
                    <div class="d-flex flex-column gap-3 mt-3">
                        {#each guruTeraktif as item (item.nama)}
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">{item.nama}</span>
                                    <Badge color="danger">{item.penugasan}</Badge>
                                </div>
                                <Progress
                                    value={(item.penugasan / maxGuru) * 100}
                                    color="danger"
                                    style="height: 8px"
                                />
                            </div>
                        {/each}
                    </div>
                {:else}
                    <p class="text-muted small mb-0">Belum ada data.</p>
                {/if}
            </CardBody>
        </Card>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-7">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-info"></i>
                    Jumlah Siswa per Kelas
                </CardTitle>
                <div class="table-responsive mt-2">
                    <Table size="sm" striped hover class="mb-0">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th class="w-50">Kepadatan</th>
                                <th class="text-end">Siswa</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each siswaPerKelas as item (item.nama)}
                                <tr>
                                    <td class="fw-semibold">{item.nama}</td>
                                    <td>
                                        <Progress
                                            value={(item.siswa / maxSiswaKelas) * 100}
                                            color="info"
                                            style="height: 8px"
                                        />
                                    </td>
                                    <td class="text-end">
                                        <Badge color="info" pill>{item.siswa}</Badge>
                                    </td>
                                </tr>
                            {:else}
                                <tr>
                                    <td colspan="3" class="text-muted small">
                                        Tidak ada data siswa berkelas untuk tahun
                                        ajaran ini. Pilih tahun ajaran lain atau
                                        "Semua".
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </Table>
                </div>
            </CardBody>
        </Card>
    </div>

    <div class="col-12 col-xl-5">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody>
                <CardTitle class="h6 fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check text-primary"></i>
                    Penugasan per Tahun Ajaran
                </CardTitle>
                <div class="table-responsive mt-2">
                    <Table size="sm" hover class="mb-0">
                        <thead>
                            <tr>
                                <th>Tahun Ajaran</th>
                                <th class="text-end">Penugasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each penugasanPerTahun as item (item.name)}
                                <tr>
                                    <td>
                                        {item.name}
                                        {#if item.active}
                                            <Badge color="success" pill>Aktif</Badge>
                                        {/if}
                                    </td>
                                    <td class="text-end fw-semibold">{item.penugasan}</td>
                                </tr>
                            {:else}
                                <tr>
                                    <td colspan="2" class="text-muted small">
                                        Belum ada data.
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </Table>
                </div>
            </CardBody>
        </Card>
    </div>
</div>

<Row class="mb-4">
    <Col>
        <p class="text-muted small mb-0">
            <i class="bi bi-clock-history me-1"></i>
            Data diperbarui saat halaman dimuat.
        </p>
    </Col>
</Row>
</div>

<style>
    .admin-dashboard {
        overflow-x: hidden;
    }

    .admin-kpi {
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--app-shell-surface, #fff);
        border: 1px solid var(--app-shell-border, #e8e6dc);
        border-radius: 0.375rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        position: relative;
        overflow: hidden;
    }

    .admin-kpi::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background-color: var(--bs-primary);
    }

    .admin-kpi--info::before {
        background-color: var(--bs-info);
    }

    .admin-kpi--success::before {
        background-color: var(--bs-success);
    }

    .admin-kpi--warning::before {
        background-color: var(--bs-warning);
    }

    .admin-kpi--secondary::before {
        background-color: var(--bs-secondary);
    }

    .admin-kpi--danger::before {
        background-color: var(--bs-danger);
    }

    .admin-kpi__icon {
        font-size: 1.5rem;
        color: var(--bs-primary);
        margin-bottom: 0.35rem;
    }

    .admin-kpi--info .admin-kpi__icon {
        color: var(--bs-info);
    }

    .admin-kpi--success .admin-kpi__icon {
        color: var(--bs-success);
    }

    .admin-kpi--warning .admin-kpi__icon {
        color: var(--bs-warning);
    }

    .admin-kpi--secondary .admin-kpi__icon {
        color: var(--bs-secondary);
    }

    .admin-kpi--danger .admin-kpi__icon {
        color: var(--bs-danger);
    }

    .admin-kpi__value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--app-shell-text, #3d3d3a);
    }

    .admin-kpi__label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--app-shell-text, #3d3d3a);
    }

    .admin-kpi__sub {
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
        margin-top: 0.1rem;
    }
</style>
