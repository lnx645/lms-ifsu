<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import {
        Button, Card, CardBody, CardHeader, Input, Label, Table,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Akademik from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';

    type BeritaAcara = {
        id: number; sesi_nama: string; tanggal: string;
        jumlah_peserta: number; jumlah_hadir: number;
        jumlah_tidak_hadir: number; jumlah_pelanggaran: number;
    };

    let { ujian, sesis, beritaAcaras }: {
        ujian: { id: number; judul: string };
        sesis: { id: number; nama: string }[];
        beritaAcaras: BeritaAcara[];
    } = $props();

    const form = useForm({
        sesi_ujian_id: null as number | null,
        tanggal: new Date().toISOString().slice(0, 10),
        nomor_surat: '',
        catatan: '',
        nama_pengawas: '',
        nama_kepala_sekolah: '',
    });

    function simpan(e: SubmitEvent) {
        e.preventDefault();
        form.post(Akademik.beritaAcaraStore.url({ ujian: ujian.id }), {
            onSuccess: () => form.reset('catatan'),
        });
    }
</script>

<PageHeader title="Berita Acara — {ujian.judul}" />

<div class="row g-3">
    <div class="col-lg-4">
        <Card>
            <CardHeader>Buat / Perbarui Berita Acara</CardHeader>
            <CardBody>
                <form onsubmit={simpan}>
                    <div class="mb-2">
                        <Label>Sesi</Label>
                        <Input type="select" bind:value={form.sesi_ujian_id}>
                            <option value={null}>Tanpa sesi (keseluruhan)</option>
                            {#each sesis as s}<option value={s.id}>{s.nama}</option>{/each}
                        </Input>
                    </div>
                    <div class="mb-2">
                        <Label>Tanggal</Label>
                        <Input type="date" bind:value={form.tanggal} />
                    </div>
                    <div class="mb-2">
                        <Label>Nomor Surat</Label>
                        <Input bind:value={form.nomor_surat} placeholder="Contoh: 421/123/SMK-IF/X/2026" />
                    </div>
                    <div class="mb-2">
                        <Label>Nama Pengawas</Label>
                        <Input bind:value={form.nama_pengawas} placeholder="Nama pengawas" />
                    </div>
                    <div class="mb-2">
                        <Label>Nama Kepala Sekolah</Label>
                        <Input bind:value={form.nama_kepala_sekolah} placeholder="Nama kepala sekolah" />
                    </div>
                    <div class="mb-3">
                        <Label>Catatan</Label>
                        <Input type="textarea" rows={3} bind:value={form.catatan} placeholder="Catatan kejadian selama ujian..." />
                    </div>
                    <Button color="primary" type="submit" disabled={form.processing}>Simpan</Button>
                </form>
                <p class="text-muted small mt-2 mb-0">Statistik kehadiran &amp; pelanggaran dihitung otomatis dari data sistem.</p>
            </CardBody>
        </Card>
    </div>
    <div class="col-lg-8">
        <Card>
            <CardHeader>Daftar Berita Acara</CardHeader>
            <CardBody>
                {#if beritaAcaras.length === 0}
                    <EmptyState message="Belum ada berita acara" hint="Buat berita acara per sesi pelaksanaan ujian." />
                {:else}
                    <Table responsive striped size="sm">
                        <thead><tr><th>Sesi</th><th>Tanggal</th><th>Hadir</th><th>Tidak Hadir</th><th>Pelanggaran</th><th></th></tr></thead>
                        <tbody>
                            {#each beritaAcaras as b}
                                <tr>
                                    <td>{b.sesi_nama}</td>
                                    <td>{b.tanggal}</td>
                                    <td>{b.jumlah_hadir}/{b.jumlah_peserta}</td>
                                    <td>{b.jumlah_tidak_hadir}</td>
                                    <td>{b.jumlah_pelanggaran}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href={Akademik.beritaAcaraPdf.url({ ujian: ujian.id, beritaAcara: b.id })} target="_blank" rel="noopener">Cetak PDF</a>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </Table>
                {/if}
            </CardBody>
        </Card>
    </div>
</div>
