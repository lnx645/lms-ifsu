<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    let { penugasan, mode: modeAwal }: {
        penugasan: { value: number; label: string }[];
        mode: string;
    } = $props();

    let mode = $state<'santai'>('santai');

    const form = useForm({
        mode: mode,
        guru_kelas_id: null as number | null,
        kategori: 'kuis',
        judul: '',
        deskripsi: '',
        durasi_menit: 60,
    });

    $effect(() => {
        form.mode = mode;
        form.kategori = mode === 'santai' ? 'kuis' : 'uh';
    });

    function submit() {
        form.post('/app/guru/ujian/wizard/info');
    }
</script>

<PageHeader title="Buat Ujian Baru" subtitle="Langkah 1 dari 3 — Informasi dasar" />

<!-- Progress -->
<div class="d-flex gap-2 mb-3">
    {#each Array(3) as _, i}
        <div class="flex-fill rounded" style="height:6px;background:{i === 0 ? 'var(--bs-primary)' : 'var(--bs-secondary-bg)'}"></div>
    {/each}
</div>

<Card class="mb-3">
    <CardHeader>Pilih Mode Ujian</CardHeader>
    <CardBody>
        <div class="row g-2">
            <div class="col-12">
                <button
                    type="button"
                    class="btn w-100 p-3 {mode === 'santai' ? 'btn-primary' : 'btn-outline-primary'}"
                    onclick={() => (mode = 'santai')}
                >
                    <i class="bi bi-lightning-charge fs-4 d-block mb-1"></i>
                    <strong>Santai</strong>
                    <div class="small">Kuis / Ulangan Harian<br/>3 langkah, &lt; 5 menit</div>
                </button>
            </div>

        </div>
    </CardBody>
</Card>

<Card>
    <CardHeader>Informasi Dasar</CardHeader>
    <CardBody>
        <div class="mb-3">
            <label class="form-label" for="w-penugasan">Kelas & Mata Pelajaran</label>
            <select id="w-penugasan" class="form-select" bind:value={form.guru_kelas_id}>
                <option value={null}>— Pilih —</option>
                {#each penugasan as p}<option value={p.value}>{p.label}</option>{/each}
            </select>
            {#if form.errors.guru_kelas_id}<div class="text-danger small">{form.errors.guru_kelas_id}</div>{/if}
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="w-kategori">Kategori</label>
                <select id="w-kategori" class="form-select" bind:value={form.kategori}>
                    {#if mode === 'santai'}
                        <option value="kuis">Kuis</option>
                        <option value="uh">Ulangan Harian</option>
                    {:else}
                        <option value="uh">Ulangan Harian</option>
                        <option value="tugas">Tugas</option>
                    {/if}
                </select>
                {#if mode === 'resmi'}
                    <div class="form-text text-warning">
                        <i class="bi bi-info-circle me-1"></i>
                        UTS/UAS/USBK dibuat oleh panitia. Guru hanya mengisi soal.
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <label class="form-label" for="w-durasi">Durasi (menit)</label>
                <input id="w-durasi" type="number" class="form-control" bind:value={form.durasi_menit} min="5" max="600" />
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label" for="w-judul">Judul Ujian</label>
            <input id="w-judul" class="form-control" bind:value={form.judul} placeholder="cth: Kuis Bab 1 — Variabel" />
            {#if form.errors.judul}<div class="text-danger small">{form.errors.judul}</div>{/if}
        </div>

        <div class="mt-3">
            <label class="form-label" for="w-deskripsi">Deskripsi (opsional)</label>
            <textarea id="w-deskripsi" class="form-control" rows="2" bind:value={form.deskripsi}></textarea>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <Button color="primary" onclick={submit} disabled={form.processing || !form.guru_kelas_id || !form.judul}>
                Lanjut <i class="bi bi-arrow-right ms-1"></i>
            </Button>
        </div>
    </CardBody>
</Card>
