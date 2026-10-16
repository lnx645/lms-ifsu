<script lang="ts">
    import { Badge, Card, CardBody, CardHeader, Progress } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Pengecoh = { opsi_id: number; teks: string; benar: boolean; dipilih: number; persentase: number };
    type HasilSoal = {
        soal_id: number; urutan: number; tipe: string; kode_kd: string | null;
        pertanyaan: string; jumlah_responden: number;
        taraf_kesukaran: number | null; kategori_kesukaran: string | null;
        daya_beda: number | null; kategori_daya_beda: string | null;
        pengecoh: Pengecoh[];
    };

    let { ujian, hasil }: {
        ujian: { id: number; judul: string };
        hasil: { soal: HasilSoal[]; reliabilitas: number | null; jumlah_responden: number };
    } = $props();

    function badgeKesukaran(k: string | null) {
        return k === 'sukar' ? 'danger' : k === 'sedang' ? 'warning' : k === 'mudah' ? 'success' : 'secondary';
    }
    function badgeDayaBeda(k: string | null) {
        return k === 'baik' ? 'success' : k === 'cukup' ? 'warning' : 'danger';
    }
    function warnaReliabilitas(r: number | null) {
        if (r === null) return 'secondary';
        return r >= 0.7 ? 'success' : r >= 0.5 ? 'warning' : 'danger';
    }
</script>

<PageHeader title="Analisis Butir Soal — {ujian.judul}" />

<div class="row g-2 g-md-3 mb-3">
    <div class="col-4">
        <Card class="h-100"><CardBody class="text-center p-2 p-md-3">
            <div class="fs-4 fs-md-2">{hasil.jumlah_responden}</div>
            <div class="text-muted small">Responden</div>
        </CardBody></Card>
    </div>
    <div class="col-4">
        <Card class="h-100"><CardBody class="text-center p-2 p-md-3">
            <div class="fs-4 fs-md-2">
                {#if hasil.reliabilitas !== null}
                    <Badge color={warnaReliabilitas(hasil.reliabilitas)} pill>{hasil.reliabilitas}</Badge>
                {:else}–{/if}
            </div>
            <div class="text-muted small">Reliabilitas α</div>
        </CardBody></Card>
    </div>
    <div class="col-4">
        <Card class="h-100"><CardBody class="text-center p-2 p-md-3">
            <div class="fs-4 fs-md-2">{hasil.soal.length}</div>
            <div class="text-muted small">Butir Soal</div>
        </CardBody></Card>
    </div>
</div>

{#if hasil.soal.length === 0}
    <EmptyState message="Belum ada data" hint="Analisis membutuhkan minimal satu pengerjaan yang selesai." />
{:else}
    <div class="row g-2 g-md-3">
        {#each hasil.soal as s}
            <div class="col-12 col-lg-6">
                <Card class="h-100">
                    <CardHeader class="py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Soal {s.urutan}</span>
                        <span>
                            <Badge color="secondary" class="me-1">{s.tipe}</Badge>
                            {#if s.kode_kd}<Badge color="info">{s.kode_kd}</Badge>{/if}
                        </span>
                    </CardHeader>
                    <CardBody class="py-2">
                        <p class="small mb-2 text-truncate-2">{s.pertanyaan}</p>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Taraf kesukaran</span>
                                <span>
                                    {#if s.taraf_kesukaran !== null}
                                        <strong>{s.taraf_kesukaran}</strong>
                                        <Badge color={badgeKesukaran(s.kategori_kesukaran)} class="ms-1">{s.kategori_kesukaran}</Badge>
                                    {:else}<span class="text-muted">–</span>{/if}
                                </span>
                            </div>
                            {#if s.taraf_kesukaran !== null}
                                <Progress value={s.taraf_kesukaran * 100} color={badgeKesukaran(s.kategori_kesukaran)} style="height:6px" />
                            {/if}
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Daya beda</span>
                                <span>
                                    {#if s.daya_beda !== null}
                                        <strong>{s.daya_beda}</strong>
                                        <Badge color={badgeDayaBeda(s.kategori_daya_beda)} class="ms-1">{s.kategori_daya_beda}</Badge>
                                    {:else}<span class="text-muted">–</span>{/if}
                                </span>
                            </div>
                            {#if s.daya_beda !== null}
                                <Progress value={Math.max(0, Math.min(100, s.daya_beda * 100))} color={badgeDayaBeda(s.kategori_daya_beda)} style="height:6px" />
                            {/if}
                        </div>

                        {#if s.pengecoh.length > 0}
                            <details class="small">
                                <summary class="text-muted" style="cursor:pointer">Pengecoh ({s.pengecoh.length} opsi)</summary>
                                <div class="mt-1">
                                    {#each s.pengecoh as p}
                                        <div class="d-flex justify-content-between {p.benar ? 'fw-bold text-success' : 'text-muted'}">
                                            <span class="text-truncate me-2">{p.teks} {p.benar ? '✓' : ''}</span>
                                            <span class="flex-shrink-0">{p.persentase}%</span>
                                        </div>
                                    {/each}
                                </div>
                            </details>
                        {/if}
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>

    <Card class="mt-3">
        <CardBody class="py-2">
            <p class="text-muted small mb-1"><strong>Panduan:</strong></p>
            <ul class="text-muted small mb-0 ps-3">
                <li>Taraf kesukaran: &lt;0,3 <strong>sukar</strong> · 0,3–0,7 <strong>sedang</strong> · &gt;0,7 <strong>mudah</strong></li>
                <li>Daya beda: ≥0,4 <strong>baik</strong> · 0,2–0,4 <strong>cukup</strong> · &lt;0,2 <strong>jelek</strong> (kelompok atas vs bawah 27%)</li>
                <li>Reliabilitas α: ≥0,7 <strong>baik</strong> · 0,5–0,7 <strong>cukup</strong> · &lt;0,5 <strong>rendah</strong></li>
                <li>Pengecoh yang tidak dipilih siapa pun sebaiknya diganti.</li>
            </ul>
        </CardBody>
    </Card>
{/if}

<style>
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .fs-md-2 { font-size: 1rem; }
    @media (min-width: 768px) {
        .fs-md-2 { font-size: 2rem; }
    }
</style>
