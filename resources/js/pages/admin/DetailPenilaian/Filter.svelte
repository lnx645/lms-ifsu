<script lang="ts">
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { changePenugasan, resetPenugasan } from '@/lib/penugasan';
    import { inertia } from '@inertiajs/svelte';
    import type {
        GuruKelasInfo,
        PenugasanOption,
        SiswaNilaiItem,
    } from '@/types/models';

    interface Props {
        penilaian: { id: number; nama: string };
        penugasan: PenugasanOption[];
        guruKelasInfo: GuruKelasInfo | null;
        selectedGuruKelasId: string | null;
        siswas: SiswaNilaiItem[] | null;
    }

    let {
        penilaian,
        penugasan = [],
        guruKelasInfo = null,
        selectedGuruKelasId = null,
        siswas = null,
    }: Props = $props();

    const sudahDinilai = (): number =>
        (siswas ?? []).filter((s) => s.nilai !== null).length;

    const totalSiswa = (): number => (siswas ?? []).length;

    const pctTerisi = (): number =>
        totalSiswa() ? Math.round((sudahDinilai() / totalSiswa()) * 100) : 0;
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Input Nilai"
        subtitle="Pilih penugasan lalu isi nilai untuk tiap siswa."
    >
        {#snippet actions()}
            <a
                use:inertia
                href={PenilaianController.index().url}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Daftar Penilaian
            </a>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="penugasan-filter" class="form-label fw-semibold mb-1">
                        <i class="bi bi-funnel me-1"></i>Pilih Penugasan
                    </label>
                    <div class="input-group">
                        <select
                            id="penugasan-filter"
                            class="form-select"
                            value={selectedGuruKelasId ?? ''}
                            onchange={changePenugasan}
                        >
                            <option value="">-- Pilih kelas &amp; matpel --</option>
                            {#each penugasan as g (g.value)}
                                <option value={g.value}>{g.label}</option>
                            {/each}
                        </select>
                        {#if selectedGuruKelasId}
                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                title="Hapus pilihan"
                                onclick={resetPenugasan}
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        {/if}
                    </div>
                </div>
                {#if guruKelasInfo && siswas}
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <Badge color="light" pill class="border">
                                <i class="bi bi-people me-1"></i>{guruKelasInfo.kelas ?? 'Kelas'}
                                · {guruKelasInfo.matpel ?? 'Matpel'}
                            </Badge>
                            <Badge color="secondary" pill>
                                <i class="bi bi-person-lines-fill me-1"></i>{totalSiswa()} siswa
                            </Badge>
                            <Badge color={pctTerisi() === 100 ? 'success' : 'info'} pill>
                                <i class="bi bi-check2-circle me-1"></i>
                                {sudahDinilai()}/{totalSiswa()} dinilai · {pctTerisi()}%
                            </Badge>
                        </div>
                        <div class="progress mt-2" style="height: 6px">
                            <div
                                class="progress-bar bg-success"
                                style="width: {pctTerisi()}%"
                                role="progressbar"
                            ></div>
                        </div>
                    </div>
                {/if}
            </div>
        </CardBody>
    </Card>

    {#if guruKelasInfo}
        {#if !siswas || siswas.length === 0}
            <EmptyState
                icon="bi-person-x"
                message="Tidak ada siswa aktif"
                hint="Tidak ditemukan siswa aktif pada kelas tahun ajaran ini."
                variant="card"
            />
        {:else}
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-0">
                    <div class="table-responsive" style="max-height: 65vh">
                        <table class="table table-hover align-middle mb-0 compact-table">
                            <thead>
                                <tr>
                                    <th class="text-muted" style="width: 2.5rem">No</th>
                                    <th style="min-width: 10rem">Nama Siswa</th>
                                    <th class="text-muted" style="width: 6rem">NISN</th>
                                    <th class="text-end" style="min-width: 6rem">Nilai</th>
                                    <th style="width: 7rem">Sumber</th>
                                    <th style="min-width: 8rem">Guru</th>
                                    <th class="text-end" style="width: 8rem">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each siswas as s, i (s.nisn)}
                                    <tr class={s.nilai === null ? 'table-warning' : ''}>
                                        <td class="text-muted">{i + 1}</td>
                                        <td class="fw-semibold">{s.nama}</td>
                                        <td class="text-muted small">{s.nisn}</td>
                                        <td class="text-end text-nowrap">
                                            {#if s.nilai !== null}
                                                <span class="fw-semibold">{s.nilai}</span>
                                            {:else}
                                                <span class="text-muted">Belum dinilai</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {#if s.sumber === 'tugas'}
                                                <span class="badge text-bg-info rounded-pill"
                                                    >Dari Tugas</span
                                                >
                                            {:else if s.sumber === 'manual'}
                                                <span
                                                    class="badge text-bg-light border rounded-pill"
                                                    >Manual</span
                                                >
                                            {:else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                        <td class="text-muted small">{s.guru ?? '—'}</td>
                                        <td class="text-end">
                                            <a
                                                use:inertia
                                                href={DetailPenilaianController.detail(
                                                    {
                                                        penilaian: penilaian.id,
                                                        guruKelas: guruKelasInfo.id,
                                                        siswa: s.nisn,
                                                    },
                                                ).url}
                                                class="btn btn-sm {s.nilai === null
                                                    ? 'btn-primary'
                                                    : 'btn-outline-primary'}"
                                            >
                                                <i
                                                    class="bi {s.nilai === null
                                                        ? 'bi-pencil-square'
                                                        : 'bi-pencil'} me-1"
                                                ></i>
                                                {s.nilai === null ? 'Input Nilai' : 'Ubah Nilai'}
                                            </a>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </CardBody>
            </Card>
        {/if}
    {:else}
        <EmptyState
            icon="bi-clipboard2-data"
            message="Belum ada penugasan dipilih"
            hint="Pilih kelas &amp; mata pelajaran di atas untuk mulai mengisi nilai."
            variant="card"
        />
    {/if}
</div>

<style>
    .compact-table {
        font-size: 0.8125rem;
    }

    :global(.compact-table thead th) {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: var(--bs-body-bg, #fff);
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.08);
        white-space: nowrap;
        text-transform: none;
        letter-spacing: 0;
        padding: 0.5rem 0.6rem;
    }

    :global(.compact-table td) {
        padding: 0.45rem 0.6rem;
        font-variant-numeric: tabular-nums;
    }
</style>
