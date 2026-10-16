<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import { changePenugasan, resetPenugasan } from '@/lib/penugasan';
    import { predikat, rataRata } from '@/lib/nilai';
    import type {
        GuruKelasInfo,
        PenugasanOption,
        RekapKolom,
        RekapSiswaItem,
    } from '@/types/models';

    let {
        penugasan,
        guruKelasInfo,
        selectedGuruKelasId,
        kolom,
        siswas,
    }: {
        penugasan: PenugasanOption[];
        guruKelasInfo: GuruKelasInfo | null;
        selectedGuruKelasId: number | null;
        kolom: RekapKolom[] | null;
        siswas: RekapSiswaItem[] | null;
    } = $props();

    function rataRataKolom(idx: number): number | null {
        return rataRata((siswas ?? []).map((s) => s.nilai[idx]));
    }

    const rataRataUmum = (): number | null =>
        rataRata((siswas ?? []).map((s) => s.rata_rata));

    const terisi = (): number => {
        const total = (siswas ?? []).reduce((acc, s) => acc + s.nilai.filter((n) => n !== null).length, 0);
        const maks = ((siswas ?? []).length || 1) * (kolom?.length || 1);
        return Math.round((total / maks) * 100);
    };
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Rekap Nilai"
        subtitle="Leger nilai siswa per kelas &amp; mata pelajaran."
    >
        {#snippet actions()}
            <a
                use:inertia
                href={PenilaianController.index().url}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Input Nilai
            </a>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="rekap-penugasan" class="form-label fw-semibold mb-1">
                        <i class="bi bi-funnel me-1"></i>Pilih Penugasan
                    </label>
                    <div class="input-group">
                        <select
                            id="rekap-penugasan"
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
                {#if guruKelasInfo && kolom && siswas}
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap gap-2">
                            <Badge color="primary" pill>
                                <i class="bi bi-people me-1"></i>{guruKelasInfo.kelas ?? 'Kelas'}
                            </Badge>
                            <Badge color="info" pill>
                                <i class="bi bi-book me-1"></i>{guruKelasInfo.matpel ?? 'Matpel'}
                            </Badge>
                            <Badge color="secondary" pill>
                                <i class="bi bi-person-lines-fill me-1"></i>{siswas.length} siswa
                            </Badge>
                            <Badge color="secondary" pill>
                                <i class="bi bi-clipboard-check me-1"></i>{kolom.length} jenis penilaian
                            </Badge>
                            <Badge color={terisi() === 100 ? 'success' : 'light'} pill>
                                <i class="bi bi-percent me-1"></i>{terisi()}% terisi
                            </Badge>
                        </div>
                    </div>
                {/if}
            </div>
        </CardBody>
    </Card>

    {#if guruKelasInfo}
        {#if !kolom || kolom.length === 0 || !siswas || siswas.length === 0}
            <EmptyState
                icon="bi-journal-x"
                message="Belum ada data nilai untuk ditampilkan"
                hint="Nilai akan muncul setelah guru menilai tugas atau memasukkan nilai manual di penugasan ini."
                variant="card"
            />
        {:else}
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-0">
                    <div class="table-responsive rekap-scroll" style="max-height: 65vh">
                        <table class="table table-striped table-hover align-middle mb-0 rekap-table">
                            <thead>
                                <tr>
                                    <th class="rekap-sticky text-muted" style="width: 2rem; left: 0">No</th>
                                    <th class="rekap-sticky" style="min-width: 11rem; left: 2rem">Nama Siswa</th>
                                    <th class="text-muted" style="width: 5rem">NISN</th>
                                    {#each kolom as k (k.id)}
                                        <th class="text-end" style="min-width: 8rem">
                                            {k.nama}
                                            <span class="text-muted fw-normal small">maks {k.nilai_maks ?? '—'}</span>
                                        </th>
                                    {/each}
                                    <th class="text-end" style="min-width: 7rem">
                                        Rata-rata
                                        <span class="text-muted fw-normal small">akhir</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each siswas as siswa, i (siswa.nisn)}
                                    <tr>
                                        <td class="rekap-sticky text-muted" style="left: 0">{i + 1}</td>
                                        <td class="rekap-sticky fw-semibold" style="left: 2rem">
                                            {siswa.nama}
                                        </td>
                                        <td class="text-muted small">{siswa.nisn}</td>
                                        {#each siswa.nilai as n, j (j)}
                                            <td class="text-end text-nowrap">
                                                {#if n !== null}
                                                    {@const p = predikat(n, kolom[j].nilai_maks)}
                                                    <span class="fw-semibold">{n}</span>
                                                    {#if p}
                                                        <span class="small {p.kelas}" title={p.label}>
                                                            {p.huruf}
                                                        </span>
                                                    {/if}
                                                {:else}
                                                    <span class="text-muted">—</span>
                                                {/if}
                                            </td>
                                        {/each}
                                        <td class="text-end text-nowrap">
                                            {#if siswa.rata_rata !== null}
                                                {@const p = predikat(siswa.rata_rata, 100)}
                                                <span class="fw-bold text-success">{siswa.rata_rata}</span>
                                                {#if p}
                                                    <span class="small {p.kelas}" title={p.label}>
                                                        {p.huruf} · {p.label}
                                                    </span>
                                                {/if}
                                            {:else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                            <tfoot class="table-light fw-semibold">
                                <tr>
                                    <td class="rekap-sticky text-muted" style="left: 0"></td>
                                    <td class="rekap-sticky" style="left: 2rem">Rata-rata kelas</td>
                                    <td></td>
                                    {#each kolom as k, j (k.id)}
                                        <td class="text-end text-nowrap">
                                            {#if rataRataKolom(j) !== null}
                                                {rataRataKolom(j)}
                                            {:else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                    {/each}
                                    <td class="text-end text-nowrap">
                                        {#if rataRataUmum() !== null}
                                            {rataRataUmum()}
                                        {:else}
                                            <span class="text-muted">—</span>
                                        {/if}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    {#if (kolom?.length ?? 0) >= 4}
                        <div class="text-muted small px-3 py-2 border-top">
                            <i class="bi bi-arrows-expand me-1"></i>
                            Geser tabel ke kiri/kanan untuk melihat semua kolom penilaian — nama siswa
                            tetap terlihat di sisi kiri.
                        </div>
                    {/if}
                </CardBody>
            </Card>
        {/if}
    {:else}
        <EmptyState
            icon="bi-clipboard2-data"
            message="Belum ada penugasan dipilih"
            hint="Pilih kelas &amp; mata pelajaran di atas untuk melihat rekap nilai."
            variant="card"
        />
    {/if}
</div>

<style>
    .rekap-scroll {
        scrollbar-width: thin;
    }

    :global(.rekap-table) {
        min-width: max-content;
        width: 100%;
        font-size: 0.8125rem;
    }

    :global(.rekap-table td),
    :global(.rekap-table th) {
        padding: 0.35rem 0.5rem;
        font-variant-numeric: tabular-nums;
    }

    :global(.rekap-table thead th) {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: var(--bs-body-bg, #fff);
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.08);
        white-space: nowrap;
        text-transform: none;
        letter-spacing: 0;
    }

    :global(.rekap-table .rekap-sticky) {
        position: sticky;
        background-color: var(--bs-body-bg, #fff);
    }

    :global(.rekap-table thead .rekap-sticky) {
        top: 0;
        z-index: 3;
    }

    :global(.rekap-table tbody .rekap-sticky),
    :global(.rekap-table tfoot .rekap-sticky) {
        z-index: 1;
        box-shadow: inset -1px 0 0 rgba(0, 0, 0, 0.08);
    }

    :global(.rekap-table tfoot .rekap-sticky) {
        background-color: var(--bs-secondary-bg, #f8f9fa);
    }
</style>
