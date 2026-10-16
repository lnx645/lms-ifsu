<script lang="ts">
    import 'katex/dist/katex.min.css';
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { KATEGORI_INFO, STATUS_PENGERJAAN_INFO, type KategoriUjian } from '@/lib/ujian';
    import HasilUjianController from '@/actions/App/Http/Controllers/Guru/HasilUjianController';
    import UjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import AkademikController from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';
    import { confirm } from '@/lib/confirm.svelte';

    let konfirmTarik = $state(false);

    function tarikKeNilai() {
        router.post(`/app/guru/ujian/${ujian.id}/hasil/tarik-nilai`, {}, {
            onSuccess: () => (konfirmTarik = false),
        });
    }

    async function resetPengerjaan(nama: string, pengerjaanId: number) {
        const ok = await confirm.show({
            title: 'Reset Pengerjaan',
            message: `Reset pengerjaan ${nama}? Jawaban & pelanggaran dihapus, siswa dapat mengulang dari awal.`,
            confirmText: 'Ya, Reset',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(AkademikController.resetPengerjaan({ ujian: ujian.id, pengerjaan: pengerjaanId }).url);
    }

    type SiswaHasil = {
        nisn: string;
        nama: string;
        pengerjaan_id: number | null;
        status: string;
        nilai_total: number | null;
        nilai_objektif: number | null;
        nilai_esai: number | null;
        jumlah_pelanggaran: number;
        submitted_at: string | null;
    };

    let {
        ujian,
        siswas,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: KategoriUjian;
            nilai_maks: number;
            kelas: string | null;
            matpel: string | null;
            jumlah_soal: number;
            ada_esai: boolean;
        };
        siswas: SiswaHasil[];
    } = $props();
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Daftar Ujian
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">
                {ujian.judul}
                <Badge color={KATEGORI_INFO[ujian.kategori].color} pill class="ms-1">
                    {KATEGORI_INFO[ujian.kategori].label}
                </Badge>
            </h1>
            <div class="text-muted small">
                {ujian.kelas} · {ujian.matpel} · {ujian.jumlah_soal} soal
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">

            {#if ujian.ada_esai}
                <Badge color="warning" pill>Ada soal esai — perlu koreksi manual</Badge>
            {/if}
            {#if !konfirmTarik}
                <Button size="sm" color="success" onclick={() => (konfirmTarik = true)}>
                    <i class="bi bi-download me-1"></i>Tarik ke Nilai
                </Button>
            {:else}
                <span class="small text-muted">Tarik nilai ke Penilaian?</span>
                <Button size="sm" color="success" onclick={tarikKeNilai}>
                    <i class="bi bi-check-lg me-1"></i>Ya
                </Button>
                <Button size="sm" color="outline-secondary" onclick={() => (konfirmTarik = false)}>Batal</Button>
            {/if}
        </div>
    </div>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Objektif</th>
                            <th>Esai</th>
                            <th>Nilai</th>
                            <th>Pelanggaran</th>
                            <th>Dikumpulkan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each siswas as s (s.nisn)}
                            <tr>
                                <td>
                                    <div class="fw-semibold">{s.nama}</div>
                                    <div class="text-muted small">{s.nisn}</div>
                                </td>
                                <td>
                                    <Badge color={STATUS_PENGERJAAN_INFO[s.status]?.color ?? 'secondary'} pill>
                                        {STATUS_PENGERJAAN_INFO[s.status]?.label ?? s.status}
                                    </Badge>
                                </td>
                                <td>{s.nilai_objektif ?? '—'}</td>
                                <td>{s.nilai_esai ?? '—'}</td>
                                <td class="fw-semibold">
                                    {s.nilai_total ?? '—'}
                                </td>
                                <td>
                                    {#if s.jumlah_pelanggaran > 0}
                                        <Badge color="danger" pill>{s.jumlah_pelanggaran}</Badge>
                                    {:else}
                                        <span class="text-muted">0</span>
                                    {/if}
                                </td>
                                <td class="small text-muted">{s.submitted_at ?? '—'}</td>
                                <td class="text-end">
                                    {#if s.pengerjaan_id}
                                        <a
                                            use:inertia
                                            href={HasilUjianController.show({ ujian: ujian.id, pengerjaan: s.pengerjaan_id }).url}
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <Button
                                            size="sm"
                                            color="outline-danger"
                                            onclick={() => resetPengerjaan(s.nama, s.pengerjaan_id!)}
                                            title="Reset pengerjaan (hapus jawaban, siswa mengulang)"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </Button>
                                    {:else}
                                        <span class="text-muted small">Belum mengerjakan</span>
                                    {/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </CardBody>
    </Card>
</div>
