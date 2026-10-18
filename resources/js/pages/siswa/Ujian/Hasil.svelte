<script lang="ts">
    import { Card, CardBody, Button, Badge, Progress } from '@sveltestrap/sveltestrap';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    let { ujian, hasil }: {
        ujian: { id: number; judul: string; nilai_maks: number | null; kkm: number | null };
        hasil: {
            nilai_total: number | null;
            nilai_objektif: number | null;
            jumlah_benar: number | null;
            jumlah_salah: number | null;
            jumlah_kosong: number | null;
            lulus_kkm: boolean | null;
            submitted_at: string | null;
        };
    } = $props();

    const persen = $derived(ujian.nilai_maks ? Math.round(((hasil.nilai_total ?? 0) / ujian.nilai_maks) * 100) : 0);
</script>

<div class="container-fluid px-0">
    <div class="mb-4 text-center">
        <h4 class="fw-bold mb-1">{ujian.judul}</h4>
        <p class="text-muted">Dikumpulkan: {hasil.submitted_at ?? '-'}</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <Card class="text-center mb-3">
                <CardBody class="py-5">
                    <p class="text-muted mb-1">Nilai Kamu</p>
                    <h1 class="display-3 fw-bold mb-2">{hasil.nilai_total ?? '-'}</h1>
                    {#if ujian.nilai_maks}<p class="text-muted">dari {ujian.nilai_maks}</p>{/if}
                    <Progress value={persen} color={persen >= 75 ? 'success' : persen >= 60 ? 'warning' : 'danger'} class="mb-3" />
                    {#if hasil.lulus_kkm !== null}
                        {#if hasil.lulus_kkm}
                            <Badge color="success" class="fs-6">Lulus KKM ({ujian.kkm})</Badge>
                        {:else}
                            <Badge color="danger" class="fs-6">Belum Lulus KKM ({ujian.kkm})</Badge>
                        {/if}
                    {/if}
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    <h6 class="fw-bold mb-3">Rincian</h6>
                    <table class="table table-sm">
                        <tbody>
                            <tr><td>Jawaban benar</td><td class="text-end fw-bold text-success">{hasil.jumlah_benar ?? '-'}</td></tr>
                            <tr><td>Jawaban salah</td><td class="text-end fw-bold text-danger">{hasil.jumlah_salah ?? '-'}</td></tr>
                            <tr><td>Tidak dijawab</td><td class="text-end fw-bold text-muted">{hasil.jumlah_kosong ?? '-'}</td></tr>
                        </tbody>
                    </table>
                    <Button color="primary" block href={SiswaUjianController.index.url()}>Kembali ke Daftar Ujian</Button>
                </CardBody>
            </Card>
        </div>
    </div>
</div>
