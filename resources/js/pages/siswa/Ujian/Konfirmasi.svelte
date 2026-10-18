<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Card, CardBody, Button, Alert, Input } from '@sveltestrap/sveltestrap';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type UjianInfo = {
        id: number;
        judul: string;
        deskripsi: string | null;
        tata_tertib: string | null;
        kategori: string | null;
        matpel: string | null;
        kelas: string | null;
        durasi_menit: number;
        jumlah_soal: number;
        butuh_token: boolean;
        acak_soal: boolean;
    };

    let { ujian, sisa_attempt, sedang_dikerjakan, pengerjaan_id }: {
        ujian: UjianInfo;
        sisa_attempt: number;
        sedang_dikerjakan: boolean;
        pengerjaan_id: number | null;
    } = $props();

    const form = useForm({ token: '' });

    function mulai() {
        $form.post(SiswaUjianController.mulai.url(ujian.id));
    }
</script>

<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">{ujian.judul}</h4>
        <p class="text-muted mb-0">{ujian.matpel ?? '-'} • {ujian.kelas ?? '-'}</p>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <Card class="mb-3">
                <CardBody>
                    <h6 class="fw-bold">Informasi Ujian</h6>
                    <table class="table table-sm">
                        <tbody>
                            <tr><td>Jumlah soal</td><td class="fw-bold">{ujian.jumlah_soal}</td></tr>
                            <tr><td>Durasi</td><td class="fw-bold">{ujian.durasi_menit} menit</td></tr>
                            <tr><td>Kesempatan tersisa</td><td class="fw-bold">{sisa_attempt}</td></tr>
                            <tr><td>Soal diacak</td><td>{ujian.acak_soal ? 'Ya' : 'Tidak'}</td></tr>
                        </tbody>
                    </table>
                    {#if ujian.deskripsi}
                        <h6 class="fw-bold mt-3">Deskripsi</h6>
                        <p>{ujian.deskripsi}</p>
                    {/if}
                </CardBody>
            </Card>

            {#if ujian.tata_tertib}
                <Card class="mb-3 border-warning">
                    <CardBody>
                        <h6 class="fw-bold text-warning">Tata Tertib Pengerjaan</h6>
                        <div style="white-space: pre-line;">{ujian.tata_tertib}</div>
                    </CardBody>
                </Card>
            {/if}
        </div>

        <div class="col-md-4">
            <Card>
                <CardBody>
                    {#if sedang_dikerjakan}
                        <Alert color="warning">Kamu sedang mengerjakan ujian ini.</Alert>
                        <Button color="warning" block href={SiswaUjianController.kerjakan.url(ujian.id)}>Lanjutkan Mengerjakan</Button>
                    {:else if sisa_attempt <= 0}
                        <Alert color="danger">Kesempatan mengerjakan sudah habis.</Alert>
                    {:else}
                        {#if ujian.butuh_token}
                            <label class="form-label fw-bold" for="token">Token Ujian</label>
                            <Input id="token" bind:value={$form.token} placeholder="Masukkan token" class="mb-3" />
                            {#if $form.errors.token}<div class="text-danger small mb-2">{$form.errors.token}</div>{/if}
                        {/if}
                        <Button color="primary" block onclick={mulai} disabled={$form.processing}>
                            {$form.processing ? 'Memulai...' : 'Mulai Kerjakan'}
                        </Button>
                    {/if}
                    <Button color="outline-secondary" block class="mt-2" href={SiswaUjianController.index.url()}>Kembali</Button>
                </CardBody>
            </Card>
        </div>
    </div>
</div>
