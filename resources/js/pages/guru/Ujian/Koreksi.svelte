<script lang="ts">
    import SoalRenderer from '../../../components/soal/render/SoalRenderer.svelte';
    import 'katex/dist/katex.min.css';
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { TIPE_SOAL_INFO, type TipeSoal } from '@/lib/ujian';
    import HasilUjianController from '@/actions/App/Http/Controllers/Guru/HasilUjianController';

    type Opsi = { id: number; teks: string; benar: boolean };
    type Jawaban = {
        id: number;
        soal_id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        opsi_dipilih: number[] | null;
        jawaban_teks: string | null;
        benar: boolean | null;
        skor: number | null;
        opsi: Opsi[];
        detail: Record<string, any> | null;
    };

    let {
        ujian,
        pengerjaan,
        jawabans,
        pelanggarans,
    }: {
        ujian: { id: number; judul: string; kategori: string; nilai_maks: number };
        pengerjaan: {
            id: number;
            siswa: string;
            nisn: string;
            status: string;
            nilai_objektif: number | null;
            nilai_esai: number | null;
            nilai_total: number | null;
            jumlah_pelanggaran: number;
            submitted_at: string | null;
        };
        jawabans: Jawaban[];
        pelanggarans: { jenis: string; terjadi_at: string }[];
    } = $props();

    // Nilai input per jawaban esai (jangan pakai satu form bersama karena
    // nilai tiap baris harus terkirim sesuai yang diketik guru).
    let skorInput = $state<Record<number, number>>({});
    let menyimpanId = $state<number | null>(null);
    let skorError = $state('');

    function nilai(j: Jawaban) {
        const skor = skorInput[j.id] ?? j.skor ?? 0;
        menyimpanId = j.id;
        skorError = '';
        router.post(
            HasilUjianController.nilaiEsai({
                ujian: ujian.id,
                pengerjaan: pengerjaan.id,
                jawaban: j.id,
            }).url,
            { skor },
            {
                preserveScroll: true,
                onSuccess: () => {
                    skorInput[j.id] = skor;
                },
                onError: (errors) => {
                    skorError = (errors as Record<string, string>).skor ?? 'Gagal menyimpan.';
                },
                onFinish: () => {
                    menyimpanId = null;
                },
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={HasilUjianController.index({ ujian: ujian.id }).url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Rekap Hasil
        </a>
    </div>

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3 d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h1 class="h5 mb-0">{pengerjaan.siswa}</h1>
                <div class="text-muted small">{ujian.judul} · {pengerjaan.nisn}</div>
            </div>
            <div class="text-end">
                <div class="fw-semibold">
                    Nilai: {pengerjaan.nilai_total ?? 'Menunggu koreksi'} / {ujian.nilai_maks}
                </div>
                <div class="small text-muted">
                    Objektif {pengerjaan.nilai_objektif ?? 0} · Esai {pengerjaan.nilai_esai ?? 0}
                </div>
                {#if pengerjaan.jumlah_pelanggaran > 0}
                    <Badge color="danger" pill class="mt-1">
                        {pengerjaan.jumlah_pelanggaran} pelanggaran
                    </Badge>
                {/if}
            </div>
        </CardBody>
    </Card>

    {#if pelanggarans.length}
        <Card class="border rounded-1 shadow-none mb-3">
            <CardBody class="p-3">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-shield-exclamation text-danger me-1"></i>Log Pelanggaran
                </div>
                <ul class="small mb-0">
                    {#each pelanggarans as p (p.terjadi_at + p.jenis)}
                        <li>{p.jenis} — {p.terjadi_at}</li>
                    {/each}
                </ul>
            </CardBody>
        </Card>
    {/if}

    {#each jawabans as j, i (j.soal_id)}
        <Card class="border rounded-1 shadow-none mb-2">
            <CardBody class="p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-semibold">Soal {i + 1}</span>
                    <Badge color="light" class="text-dark border">
                        {TIPE_SOAL_INFO[j.tipe]?.label ?? j.tipe}
                    </Badge>
                    <span class="text-muted small">{j.poin} poin</span>
                    {#if !j.dijawab}
                        <Badge color="secondary" pill>Tidak dijawab</Badge>
                    {:else if j.benar === true}
                        <Badge color="success" pill>Benar</Badge>
                    {:else if j.benar === false}
                        <Badge color="danger" pill>Salah</Badge>
                    {/if}
                </div>
                <div class="fw-semibold mb-2">{@html j.pertanyaan}</div>
                {@const dd = j.detail && Object.keys(j.detail).length > 0 ? j.detail : (j.opsi?.length ? { opsi: j.opsi } : {})}
                {@const jw = (() => {
                    try {
                        const t = j.jawaban_teks;
                        if (t && (t.startsWith('{') || t.startsWith('['))) return JSON.parse(t);
                        return t ?? j.opsi_dipilih ?? null;
                    } catch { return j.jawaban_teks ?? j.opsi_dipilih ?? null; }
                })()}
                <SoalRenderer
                    tipe={j.tipe}
                    detail={dd}
                    jawaban={jw}
                    disabled={true}
                    showKunci={true}
                />

                {#if j.tipe === 'esai' || j.tipe === 'uraian'}
                    <div class="input-group" style="max-width: 320px">
                        <span class="input-group-text">Skor</span>
                        <input
                            type="number"
                            min="0"
                            max={j.poin}
                            class="form-control"
                            value={skorInput[j.id] ?? j.skor ?? 0}
                            oninput={(e) => (skorInput[j.id] = Number((e.currentTarget as HTMLInputElement).value))}
                        />
                        <span class="input-group-text">/ {j.poin}</span>
                        <Button color="primary" onclick={() => nilai(j)} disabled={menyimpanId === j.id}>
                            {menyimpanId === j.id ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                    </div>
                    {#if skorError && menyimpanId === null}
                        <div class="text-danger small mt-1">{skorError}</div>
                    {/if}
                {:else}
                    <div class="small text-muted">Skor otomatis: {j.skor ?? 0}</div>
                {/if}
            </CardBody>
        </Card>
    {/each}
</div>

<style>
    .text-pre-wrap {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
