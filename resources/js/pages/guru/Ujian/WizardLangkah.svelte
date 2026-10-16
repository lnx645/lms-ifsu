<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type Paket = { id: number; kode: string; nama: string; soals?: { id: number }[] };

    let {
        ujian, mode, langkah, maksLangkah, kelengkapan, kelompokBanks = [],
    }: {
        ujian: {
            id: number; judul: string; kategori: string; durasi_menit: number;
            paket_soals?: Paket[];
            soals?: { id: number }[];
        };
        mode: 'santai' | 'resmi';
        langkah: number;
        maksLangkah: number;
        kelengkapan: string[];
        kelompokBanks?: { id: number; nama: string; soals_count: number }[];
    } = $props();

    const judulLangkah: Record<string, string> = {
        'santai-2': 'Tambah Soal',
        'santai-3': 'Review & Terbitkan',
        'resmi-2': 'Paket Soal (Varian A/B/C)',
        'resmi-3': 'Review & Serahkan ke Panitia',
    };

    // Form paket (langkah 2 resmi) — pilih bank soal bernama.
    const paketForm = useForm({ jumlah_paket: 3, soal_per_paket: 10, kelompok_bank_soal_id: null as number | null });

    function generatePaket() {
        paketForm.post(`/app/guru/ujian/${ujian.id}/wizard/paket`);
    }

    function terbit() {
        router.post(`/app/guru/ujian/${ujian.id}/wizard/terbit`);
    }

    function serahkan() {
        router.post(`/app/guru/ujian/${ujian.id}/wizard/serahkan`);
    }

    let konfirmSerahkan = $state(false);

    function kembali() {
        router.visit('/app/guru/ujian');
    }
</script>

<PageHeader title={ujian.judul} subtitle="Langkah {langkah} dari {maksLangkah} — {judulLangkah[`${mode}-${langkah}`] ?? ''}" />

<div class="d-flex gap-2 mb-3">
    {#each Array(maksLangkah) as _, i}
        <div class="flex-fill rounded" style="height:6px;background:{i < langkah ? 'var(--bs-primary)' : 'var(--bs-secondary-bg)'}"></div>
    {/each}
</div>

{#if mode === 'santai' && langkah === 2}
    <Card>
        <CardHeader>Tambah Soal</CardHeader>
        <CardBody>
            <p class="text-muted">Soal saat ini: <strong>{ujian.soals?.length ?? 0}</strong></p>
            <p class="small text-muted">
                Tambahkan soal lewat halaman kelola soal (tombol di bawah), lalu kembali ke sini untuk lanjut.
                Bisa ketik manual, import Excel, atau ambil dari bank soal.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a use:inertia href="/app/guru/ujian/{ujian.id}/soal" class="btn btn-outline-primary">
                    <i class="bi bi-list-check me-1"></i>Kelola Soal
                </a>
                <Button color="primary" onclick={() => keLangkah(3)}>
                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                </Button>
            </div>
            <p class="small text-muted mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Setelah menambah soal, kembali ke halaman ini lalu klik "Lanjut".
            </p>
        </CardBody>
    </Card>
{/if}

{#if mode === 'resmi' && langkah === 2}
    <Card>
        <CardHeader>Buat Paket Soal Otomatis</CardHeader>
        <CardBody>
            {#if (ujian.paket_soals?.length ?? 0) > 0}
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-1"></i>
                    {ujian.paket_soals!.length} paket sudah dibuat:
                    {#each ujian.paket_soals! as p}
                        <Badge color="info" class="me-1">Paket {p.kode} ({p.soals?.length ?? 0} soal)</Badge>
                    {/each}
                </div>
            {/if}
            <p class="text-muted small">
                Pilih bank soal yang mau dipakai. Sistem akan membagi soalnya secara acak ke tiap paket.
                {#if (ujian.paket_soals?.length ?? 0) > 0}Generate ulang akan menghapus paket lama.{/if}
            </p>
            <div class="mb-3">
                <label class="form-label" for="pk-bank">Bank Soal <span class="text-danger">*</span></label>
                <select id="pk-bank" class="form-select" bind:value={paketForm.kelompok_bank_soal_id}>
                    <option value={null}>— Pilih bank soal —</option>
                    {#each kelompokBanks as kb}
                        <option value={kb.id}>{kb.nama} ({kb.soals_count} soal)</option>
                    {/each}
                </select>
                {#if paketForm.errors.kelompok_bank_soal_id}
                    <div class="text-danger small">{paketForm.errors.kelompok_bank_soal_id}</div>
                {/if}
                {#if kelompokBanks.length === 0}
                    <div class="form-text">
                        Belum ada bank soal. <a use:inertia href="/app/guru/bank-soal/kelompok">Buat bank soal dulu</a>.
                    </div>
                {/if}
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="pk-jumlah">Jumlah paket</label>
                    <input id="pk-jumlah" type="number" class="form-control" bind:value={paketForm.jumlah_paket} min="1" max="10" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="pk-soal">Soal per paket</label>
                    <input id="pk-soal" type="number" class="form-control" bind:value={paketForm.soal_per_paket} min="1" max="100" />
                </div>
            </div>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                {#if (ujian.paket_soals?.length ?? 0) === 0}
                    <Button color="primary" onclick={generatePaket} disabled={paketForm.processing || !paketForm.kelompok_bank_soal_id}>
                        <i class="bi bi-magic me-1"></i>Generate {paketForm.jumlah_paket} Paket
                    </Button>
                {:else}
                    <Button color="secondary" outline onclick={() => keLangkah(3)}>
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </Button>
                    <Button color="warning" outline onclick={generatePaket} disabled={paketForm.processing || !paketForm.kelompok_bank_soal_id}>
                        <i class="bi bi-arrow-repeat me-1"></i>Generate Ulang
                    </Button>
                    <Button color="primary" onclick={() => keLangkah(3)}>
                        Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </Button>
                {/if}
            </div>
        </CardBody>
    </Card>
{/if}

{#if (mode === 'santai' && langkah === 3) || (mode === 'resmi' && langkah === 3)}
    <Card>
        <CardHeader>{mode === 'resmi' ? 'Review & Serahkan ke Panitia' : 'Review & Terbitkan'}</CardHeader>
        <CardBody>
            <dl class="row small">
                <dt class="col-4">Judul</dt><dd class="col-8">{ujian.judul}</dd>
                <dt class="col-4">Kategori</dt><dd class="col-8">{ujian.kategori.toUpperCase()}</dd>
                <dt class="col-4">Durasi</dt><dd class="col-8">{ujian.durasi_menit} menit</dd>
                {#if mode === 'resmi'}
                    <dt class="col-4">Paket soal</dt><dd class="col-8">{ujian.paket_soals?.length ?? 0} paket</dd>
                {:else}
                    <dt class="col-4">Soal</dt><dd class="col-8">{ujian.soals?.length ?? 0} soal</dd>
                {/if}
            </dl>

            {#if mode === 'resmi'}
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Setelah diserahkan, <strong>panitia</strong> yang akan mengatur jadwal, sesi, ruang, token, dan menerbitkan ujian.
                </div>
                {#if (ujian.paket_soals?.length ?? 0) === 0}
                    <div class="alert alert-warning">Buat paket soal dulu (langkah 2) sebelum diserahkan.</div>
                {/if}
                <div class="d-flex gap-2 flex-wrap">
                    <Button color="secondary" outline onclick={() => keLangkah(2)}>
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </Button>
                    {#if !konfirmSerahkan}
                        <Button color="primary" onclick={() => (konfirmSerahkan = true)} disabled={(ujian.paket_soals?.length ?? 0) === 0}>
                            <i class="bi bi-send me-1"></i>Serahkan ke Panitia
                        </Button>
                    {:else}
                        <span class="align-self-center small text-muted">Yakin serahkan ke panitia?</span>
                        <Button color="primary" onclick={serahkan}>
                            <i class="bi bi-check-lg me-1"></i>Ya, Serahkan
                        </Button>
                        <Button color="secondary" outline onclick={() => (konfirmSerahkan = false)}>Batal</Button>
                    {/if}
                </div>
            {:else}
                {#if kelengkapan.length > 0}
                    <div class="alert alert-warning">
                        <strong>Belum bisa diterbitkan:</strong>
                        <ul class="mb-0 ps-3">
                            {#each kelengkapan as k}<li>{k}</li>{/each}
                        </ul>
                    </div>
                {:else}
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-1"></i>Semua lengkap. Siap diterbitkan!
                    </div>
                {/if}

                <div class="d-flex gap-2 flex-wrap">
                    <Button color="secondary" outline onclick={() => keLangkah(2)}>
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </Button>
                    <Button color="secondary" outline onclick={kembali}>Simpan Draft</Button>
                    <Button color="success" onclick={terbit} disabled={kelengkapan.length > 0}>
                        <i class="bi bi-send me-1"></i>Terbitkan Ujian
                    </Button>
                </div>
            {/if}
        </CardBody>
    </Card>
{/if}
