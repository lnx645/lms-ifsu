<script lang="ts">
    import { inertia, Link } from '@inertiajs/svelte';
    import { Badge, Card, CardBody, Button } from '@sveltestrap/sveltestrap';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type UjianItem = {
        id: number;
        judul: string;
        kategori: string | null;
        matpel: string | null;
        kelas: string | null;
        durasi_menit: number;
        jumlah_soal: number;
        butuh_token: boolean;
        attempt_terpakai: number;
        maks_attempt: number;
        sisa_attempt: number;
        sedang_dikerjakan: boolean;
        tampilkan_hasil: boolean;
        nilai_terbaik: number | null;
    };

    let { ujians }: { ujians: UjianItem[] } = $props();

    const tersedia = $derived(ujians.filter((u) => u.sisa_attempt > 0 || u.sedang_dikerjakan));
    const selesai = $derived(ujians.filter((u) => u.sisa_attempt <= 0 && !u.sedang_dikerjakan));
</script>

<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Daftar Ujian</h4>
        <p class="text-muted mb-0">Ujian yang tersedia untuk kelasmu</p>
    </div>

    {#if tersedia.length === 0 && selesai.length === 0}
        <Card><CardBody class="text-center text-muted py-5">Belum ada ujian tersedia.</CardBody></Card>
    {/if}

    {#if tersedia.length > 0}
        <h6 class="fw-bold mb-3">Tersedia ({tersedia.length})</h6>
        <div class="row g-3 mb-4">
            {#each tersedia as u (u.id)}
                <div class="col-md-6">
                    <Card class="h-100">
                        <CardBody>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0">{u.judul}</h6>
                                {#if u.sedang_dikerjakan}
                                    <Badge color="warning">Berlangsung</Badge>
                                {:else if u.butuh_token}
                                    <Badge color="info">Token</Badge>
                                {/if}
                            </div>
                            <p class="text-muted small mb-2">{u.matpel ?? '-'} • {u.kelas ?? '-'}</p>
                            <p class="small mb-3">
                                {u.jumlah_soal} soal • {u.durasi_menit} menit •
                                Kesempatan: {u.sisa_attempt}/{u.maks_attempt}
                            </p>
                            {#if u.sedang_dikerjakan}
                                <Button color="warning" size="sm" href={SiswaUjianController.kerjakan.url(u.id)}>Lanjutkan</Button>
                            {:else}
                                <Button color="primary" size="sm" href={SiswaUjianController.konfirmasi.url(u.id)}>Kerjakan</Button>
                            {/if}
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {/if}

    {#if selesai.length > 0}
        <h6 class="fw-bold mb-3">Selesai ({selesai.length})</h6>
        <div class="row g-3">
            {#each selesai as u (u.id)}
                <div class="col-md-6">
                    <Card class="h-100">
                        <CardBody>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0">{u.judul}</h6>
                                <Badge color="success">Selesai</Badge>
                            </div>
                            <p class="text-muted small mb-2">{u.matpel ?? '-'} • {u.kelas ?? '-'}</p>
                            {#if u.tampilkan_hasil && u.nilai_terbaik !== null}
                                <p class="fw-bold mb-2">Nilai: {u.nilai_terbaik}</p>
                                <Button color="outline-primary" size="sm" href={SiswaUjianController.hasil.url(u.id)}>Lihat Hasil</Button>
                            {/if}
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {/if}
</div>
