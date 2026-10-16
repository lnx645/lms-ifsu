<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    let { ujian, penugasan = [] }: {
        ujian: any;
        penugasan?: { value: number; label: string }[];
    } = $props();

    const form = useForm({
        judul: ujian.judul ?? '',
        kategori: ujian.kategori ?? 'kuis',
        guru_kelas_id: ujian.guru_kelas_id ?? null,
        tanggal_mulai: ujian.tanggal_mulai ?? '',
        tanggal_selesai: ujian.tanggal_selesai ?? '',
        durasi_menit: ujian.durasi_menit ?? 60,
        deskripsi: ujian.deskripsi ?? '',
        nilai_maks: ujian.nilai_maks ?? 100,
        kkm: ujian.kkm ?? 75,
        acak_soal: ujian.acak_soal ?? true,
        acak_opsi: ujian.acak_opsi ?? true,
        tata_tertib: ujian.tata_tertib ?? '',
    });

    function simpan() {
        form.put(`/app/guru/ujian/${ujian.id}`, {
            onSuccess: () => router.get('/app/guru/ujian'),
        });
    }
</script>

<div class="container py-3">
    <PageHeader title="Edit Ujian" subtitle={ujian.judul} />

    <div class="row g-3">
        <div class="col-lg-8">
            <Card class="shadow-sm border-0 mb-3">
                <CardBody class="p-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle p-2"><i class="bi bi-info-circle"></i></span>
                        Informasi Dasar
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="e-judul">JUDUL UJIAN</label>
                        <input id="e-judul" class="form-control" bind:value={form.judul} placeholder="cth: Kuis Bab 1" />
                        {#if form.errors.judul}<div class="text-danger small mt-1">{form.errors.judul}</div>{/if}
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="e-kat">KATEGORI</label>
                            <select id="e-kat" class="form-select" bind:value={form.kategori}>
                                <option value="kuis">Kuis</option>
                                <option value="uh">Ulangan Harian</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="e-dur">DURASI (MENIT)</label>
                            <input id="e-dur" type="number" class="form-control" bind:value={form.durasi_menit} min="5" max="300" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="e-kelas">KELAS & MATA PELAJARAN</label>
                        <select id="e-kelas" class="form-select" bind:value={form.guru_kelas_id}>
                            <option value={null}>-- Pilih --</option>
                            {#each penugasan as p}
                                <option value={p.value}>{p.label}</option>
                            {/each}
                        </select>
                        {#if form.errors.guru_kelas_id}<div class="text-danger small mt-1">{form.errors.guru_kelas_id}</div>{/if}
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="e-tgl">MULAI</label>
                            <input id="e-tgl" type="datetime-local" class="form-control" bind:value={form.tanggal_mulai} />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="e-tgl2">DEADLINE</label>
                            <input id="e-tgl2" type="datetime-local" class="form-control" bind:value={form.tanggal_selesai} />
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="e-desk">DESKRIPSI</label>
                        <textarea id="e-desk" class="form-control" rows="2" bind:value={form.deskripsi} placeholder="Deskripsi opsional..."></textarea>
                    </div>
                </CardBody>
            </Card>

            <Card class="shadow-sm border-0">
                <CardBody class="p-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge bg-warning rounded-circle p-2"><i class="bi bi-shield-check"></i></span>
                        Tata Tertib
                    </h6>
                    <textarea id="e-tata" class="form-control" rows="4" bind:value={form.tata_tertib} placeholder="cth: Dilarang membuka buku, dilarang bekerjasama, dll..."></textarea>
                    <div class="form-text mt-2">Akan ditampilkan kepada siswa sebelum mulai ujian.</div>
                </CardBody>
            </Card>
        </div>

        <div class="col-lg-4">
            <Card class="shadow-sm border-0 mb-3">
                <CardBody class="p-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-circle p-2"><i class="bi bi-graph-up"></i></span>
                        Penilaian
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="e-kkm">KKM</label>
                        <input id="e-kkm" type="number" min="0" max="100" class="form-control" bind:value={form.kkm} />
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="e-nmaks">NILAI MAKSIMAL</label>
                        <input id="e-nmaks" type="number" min="10" max="100" class="form-control" bind:value={form.nilai_maks} />
                    </div>
                </CardBody>
            </Card>

            <Card class="shadow-sm border-0 mb-3">
                <CardBody class="p-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <span class="badge bg-info rounded-circle p-2"><i class="bi bi-shuffle"></i></span>
                        Pengacakan
                    </h6>

                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div>
                            <div class="fw-semibold small">Acak Soal</div>
                            <div class="text-muted" style="font-size: 0.8rem">Urutan soal beda tiap siswa</div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="e-acak-soal" bind:checked={form.acak_soal} style="width: 2.5rem; height: 1.4rem;" />
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between py-2">
                        <div>
                            <div class="fw-semibold small">Acak Opsi</div>
                            <div class="text-muted" style="font-size: 0.8rem">Urutan jawaban beda tiap siswa</div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" id="e-acak-opsi" bind:checked={form.acak_opsi} style="width: 2.5rem; height: 1.4rem;" />
                        </div>
                    </div>
                </CardBody>
            </Card>

            <div class="d-grid gap-2">
                <Button color="primary" size="lg" onclick={simpan} disabled={form.processing || !form.judul}>
                    {form.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </Button>
                <Button color="light" onclick={() => router.get('/app/guru/ujian')}>Batal</Button>
            </div>
        </div>
    </div>
</div>


