<script lang="ts">
    import { router, useForm } from "@inertiajs/svelte";
    import PageHeader from "@/components/PageHeader.svelte";

    let { penugasan = [] }: {
        penugasan?: { value: number; label: string }[];
    } = $props();

    const form = useForm({
        judul: "",
        kategori: "kuis",
        guru_kelas_id: null,
        tanggal_mulai: "",
        tanggal_selesai: "",
        durasi_menit: 60,
        deskripsi: "",
        nilai_maks: 100,
        kkm: 75,
        acak_soal: true,
        acak_opsi: true,
        tata_tertib: "",
    });

    let showLanjutan = $state(false);

    function simpan() {
        form.post("/app/guru/ujian", {
            onSuccess: () => router.get("/app/guru/ujian"),
        });
    }
</script>

<div class="container py-3" style="max-width: 600px">
    <PageHeader title="Buat Ujian" subtitle="Kuis / Ulangan Harian" />

    <div class="mb-3">
        <label class="form-label small fw-semibold" for="e-judul">Judul</label>
        <input id="e-judul" class="form-control" bind:value={form.judul} placeholder="cth: Kuis Bab 1" />
        {#if form.errors.judul}<div class="text-danger small">{form.errors.judul}</div>{/if}
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold" for="e-kelas">Kelas</label>
        <select id="e-kelas" class="form-select" bind:value={form.guru_kelas_id}>
            <option value={null}>-- Pilih --</option>
            {#each penugasan as p}
                <option value={p.value}>{p.label}</option>
            {/each}
        </select>
        {#if form.errors.guru_kelas_id}<div class="text-danger small">{form.errors.guru_kelas_id}</div>{/if}
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold" for="e-kat">Kategori</label>
            <select id="e-kat" class="form-select" bind:value={form.kategori}>
                <option value="kuis">Kuis</option>
                <option value="uh">UH</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold" for="e-dur">Durasi (mnt)</label>
            <input id="e-dur" type="number" class="form-control" bind:value={form.durasi_menit} min="5" max="300" />
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold" for="e-tgl">Mulai</label>
            <input id="e-tgl" type="datetime-local" class="form-control form-control-sm" bind:value={form.tanggal_mulai} />
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold" for="e-tgl2">Selesai</label>
            <input id="e-tgl2" type="datetime-local" class="form-control form-control-sm" bind:value={form.tanggal_selesai} />
        </div>
    </div>

    <button class="btn btn-link btn-sm p-0 mb-3 text-decoration-none" onclick={() => showLanjutan = !showLanjutan}>
        {showLanjutan ? "▾" : "▸"} Pengaturan lanjutan
    </button>

    {#if showLanjutan}
        <div class="mb-3">
            <label class="form-label small fw-semibold" for="e-desk">Deskripsi</label>
            <textarea id="e-desk" class="form-control" rows="2" bind:value={form.deskripsi} placeholder="Opsional..."></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold" for="e-tata">Tata tertib</label>
            <textarea id="e-tata" class="form-control" rows="3" bind:value={form.tata_tertib} placeholder="Ditampilkan ke siswa sebelum mulai..."></textarea>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold" for="e-kkm">KKM</label>
                <input id="e-kkm" type="number" min="0" max="100" class="form-control" bind:value={form.kkm} />
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold" for="e-nmaks">Nilai maks</label>
                <input id="e-nmaks" type="number" min="10" max="100" class="form-control" bind:value={form.nilai_maks} />
            </div>
        </div>

        <div class="d-flex gap-3 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="e-acak-soal" bind:checked={form.acak_soal} />
                <label class="form-check-label small" for="e-acak-soal">Acak soal</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="e-acak-opsi" bind:checked={form.acak_opsi} />
                <label class="form-check-label small" for="e-acak-opsi">Acak opsi</label>
            </div>
        </div>
    {/if}

    <div class="d-grid gap-2 mt-4">
        <button class="btn btn-primary btn-lg" onclick={simpan} disabled={form.processing || !form.judul}>
            {form.processing ? "Menyimpan..." : "Buat Ujian"}
        </button>
        <button class="btn btn-light" onclick={() => router.get("/app/guru/ujian")}>Batal</button>
    </div>
</div>
