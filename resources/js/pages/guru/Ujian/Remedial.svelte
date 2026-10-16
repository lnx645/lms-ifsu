<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Input, Label, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Akademik from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';

    let { ujian, butuhRemedial }: {
        ujian: { id: number; judul: string; kkm: number | null };
        butuhRemedial: { nisn: string; nama: string; nilai: number; sudah_remedial: boolean }[];
    } = $props();

    const kkmForm = useForm({ kkm: ujian.kkm });

    function simpanKkm(e: SubmitEvent) {
        e.preventDefault();
        kkmForm.put(Akademik.remedialKkm.url({ ujian: ujian.id }));
    }
</script>

<PageHeader title="Remedial — {ujian.judul}" />

<Card class="mb-3">
    <CardBody>
        <form onsubmit={simpanKkm} class="d-flex gap-2 align-items-end">
            <div>
                <Label>KKM</Label>
                <Input type="number" min={0} max={100} bind:value={kkmForm.kkm} style="width:120px" />
            </div>
            <Button color="primary" type="submit" disabled={kkmForm.processing}>Simpan KKM</Button>
        </form>
        <p class="text-muted small mt-2 mb-0">Siswa di bawah KKM otomatis mendapat tombol <strong>"Mulai Remedial"</strong> di dashboard mereka — tidak perlu aksi manual. Remedial tercatat sebagai attempt tambahan.</p>
    </CardBody>
</Card>

<Card>
    <CardHeader>Siswa Butuh Remedial ({butuhRemedial.length})</CardHeader>
    <CardBody>
        {#if ujian.kkm === null}
            <EmptyState message="KKM belum diatur" hint="Atur KKM terlebih dahulu untuk melihat daftar siswa remedial." />
        {:else if butuhRemedial.length === 0}
            <EmptyState message="Tidak ada siswa remedial" hint="Semua siswa mencapai KKM." />
        {:else}
            <Table responsive striped size="sm">
                <thead><tr><th>NISN</th><th>Nama</th><th>Nilai</th><th>KKM</th><th>Status</th></tr></thead>
                <tbody>
                    {#each butuhRemedial as s}
                        <tr>
                            <td>{s.nisn}</td>
                            <td>{s.nama}</td>
                            <td>{s.nilai}</td>
                            <td>{ujian.kkm}</td>
                            <td>
                                {#if s.sudah_remedial}<Badge color="info">Sudah remedial</Badge>
                                {:else}<Badge color="warning">Belum remedial</Badge>{/if}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        {/if}
    </CardBody>
</Card>
