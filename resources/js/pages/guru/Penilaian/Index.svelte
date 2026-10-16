<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import type { PenilaianItem, PenugasanOption } from '@/types/models';

    let {
        penilaian,
        penugasan,
    }: { penilaian: PenilaianItem[]; penugasan: PenugasanOption[] } =
        $props();

    let penilaianId = $state<number | null>(null);
    let penugasanId = $state<number | null>(null);
    let errorPesan = $state('');

    function lanjut() {
        if (!penilaianId) {
            errorPesan = 'Pilih jenis penilaian dulu.';
            return;
        }
        if (!penugasanId) {
            errorPesan = 'Pilih kelas & mata pelajaran dulu.';
            return;
        }
        errorPesan = '';
        router.visit(
            PenilaianController.show({
                penilaian: penilaianId,
                guruKelas: penugasanId,
            }).url,
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Penilaian"
        subtitle="Input nilai ulangan harian, PTS, sikap, dan jenis penilaian lainnya."
    >
        {#snippet actions()}
            <Button
                color="outline-primary"
                onclick={() => router.visit(PenilaianController.rekap().url)}
            >
                <i class="bi bi-list-check me-1"></i>Rekap Nilai
            </Button>
        {/snippet}
    </PageHeader>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="fw-semibold mb-3">Pilih Penilaian</div>
                    {#if penilaian.length === 0}
                        <EmptyState
                            icon="bi-clipboard-x"
                            message="Belum ada jenis penilaian aktif dari admin."
                        />
                    {:else}
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Tipe</th>
                                        <th class="text-end">Nilai Maks</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each penilaian as p (p.id)}
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"
                                                    >{p.nama}</div
                                                >
                                                {#if p.deskripsi}
                                                    <div
                                                        class="text-muted small text-truncate"
                                                        style="max-width: 300px"
                                                    >
                                                        {p.deskripsi}
                                                    </div>
                                                {/if}
                                            </td>
                                            <td>
                                                <Badge
                                                    color={
                                                        p.sumber ===
                                                        'tugas'
                                                            ? 'info'
                                                            : 'light'
                                                    }
                                                    pill
                                                >
                                                    {p.sumber === 'tugas'
                                                        ? 'Dari Tugas'
                                                        : p.tipe}
                                                </Badge>
                                            </td>
                                            <td class="text-end text-nowrap"
                                                >{p.nilai_maks}</td
                                            >
                                            <td class="text-end">
                                                <input
                                                    type="radio"
                                                    class="form-check-input"
                                                    name="pilih-penilaian"
                                                    value={p.id}
                                                    checked={penilaianId ===
                                                        p.id}
                                                    onchange={() => {
                                                        penilaianId = p.id;
                                                        errorPesan = '';
                                                    }}
                                                />
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </CardBody>
            </Card>
        </div>

        <div class="col-12 col-xl-5">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="fw-semibold mb-3">
                        Pilih Kelas &amp; Mata Pelajaran
                    </div>
                    <select
                        class="form-select"
                        value={penugasanId ?? ''}
                        onchange={(e) => {
                            penugasanId = Number(
                                (e.currentTarget as HTMLSelectElement)
                                    .value,
                            ) || null;
                            errorPesan = '';
                        }}
                    >
                        <option value="">
                            -- Pilih kelas &amp; matpel --
                        </option>
                        {#each penugasan as g (g.value)}
                            <option value={g.value}>{g.label}</option>
                        {/each}
                    </select>
                    {#if penugasan.length === 0}
                        <div class="text-muted small mt-2">
                            Kamu belum punya penugasan aktif di tahun
                            ajaran ini.
                        </div>
                    {/if}

                    {#if errorPesan}
                        <div class="invalid-feedback d-block mt-2">
                            {errorPesan}
                        </div>
                    {/if}

                    <button
                        type="button"
                        class="btn btn-primary mt-3 w-100"
                        onclick={lanjut}
                        disabled={penilaian.length === 0}
                    >
                        <i class="bi bi-clipboard2-data me-1"></i>
                        Lanjut Input Nilai
                    </button>
                </CardBody>
            </Card>
        </div>
    </div>
</div>