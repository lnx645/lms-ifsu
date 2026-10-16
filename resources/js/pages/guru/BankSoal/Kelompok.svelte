<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Button, Card, CardBody, Modal, ModalBody, ModalFooter, ModalHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Kelompok = {
        id: number; nama: string; kode?: string | null; deskripsi?: string;
        matpel_id?: number; matpel?: { name: string }; soals_count: number;
        urutan_tampil?: string; komposisi?: Record<string, { max_soal: number; persentase: number }>;
    };

    let { kelompoks, matpelOptions }: {
        kelompoks: { data: Kelompok[] };
        matpelOptions: { value: number; label: string }[];
    } = $props();

    let modalOpen = $state(false);
    let editId = $state<number | null>(null);
    let konfirmHapusId = $state<number | null>(null);
    const TIPE_SOAL = [
        { key: 'pilihan_ganda', label: 'Pilihan ganda' },
        { key: 'pilihan_ganda_kompleks', label: 'Pilihan ganda kompleks' },
        { key: 'pilihan_ganda_citra', label: 'Pilihan ganda citra' },
        { key: 'menjodohkan', label: 'Menjodohkan' },
        { key: 'menjodohkan_banyak', label: 'Menjodohkan -> Banyak' },
        { key: 'isian_singkat', label: 'Isian singkat' },
        { key: 'uraian', label: 'Uraian' },
        { key: 'mengurutkan', label: 'Mengurutkan' },
        { key: 'benar_salah', label: 'Benar/salah' },
        { key: 'setuju_tidak', label: 'Setuju/tidak' },
        { key: 'matrix_text', label: 'Table matrix input text' },
        { key: 'matrix_checkbox', label: 'Table matrix checkbox' },
        { key: 'mengelompokkan', label: 'Mengelompokkan' },
        { key: 'blank_space', label: 'Put blank space' },
        { key: 'image_labeling', label: 'Image labeling' },
        { key: 'listening', label: 'Listening' },
    ];

    const defaultKomposisi = () => Object.fromEntries(TIPE_SOAL.map(t => [t.key, { max_soal: 0, persentase: 0 }]));

    const form = useForm({
        nama: '', kode: '', deskripsi: '',
        matpel_id: null as number | null,
        urutan_tampil: 'tipe_waktu',
        komposisi: defaultKomposisi() as Record<string, { max_soal: number; persentase: number }>,
    });


    function buat() {
        form.post('/app/guru/bank-soal/kelompok', {
            onSuccess: () => { modalOpen = false; form.reset(); },
        });
    }

    function bukaEdit(k: Kelompok, e: Event) {
        e.preventDefault();
        e.stopPropagation();
        editId = k.id;
        form.nama = k.nama;
        form.kode = k.kode ?? '';
        form.deskripsi = k.deskripsi ?? '';
        form.matpel_id = k.matpel_id ?? null;
        form.urutan_tampil = k.urutan_tampil ?? 'tipe_waktu';
        // Isi komposisi dari data tersimpan
        const saved = k.komposisi ?? {};
        form.komposisi = Object.fromEntries(
            TIPE_SOAL.map(t => [t.key, {
                max_soal: saved[t.key]?.max_soal ?? 0,
                persentase: saved[t.key]?.persentase ?? 0,
            }])
        );
        modalOpen = true;
    }

    function simpanEdit() {
        if (!editId) return buat();
        form.put(`/app/guru/bank-soal/kelompok/${editId}`, {
            onSuccess: () => { modalOpen = false; form.reset(); editId = null; },
        });
    }

    function bukaBuat() {
        editId = null;
        form.reset();
        modalOpen = true;
    }

    function hapus(k: Kelompok, e: Event) {
        e.preventDefault();
        e.stopPropagation();
        router.delete(`/app/guru/bank-soal/kelompok/${k.id}`, {
            onSuccess: () => (konfirmHapusId = null),
        });
    }
</script>

<PageHeader title="Bank Soal" subtitle="Buat wadah bank soal, lalu isi soal-soalnya">
    {#snippet actions()}
        <Button color="primary" onclick={bukaBuat}>
            <i class="bi bi-plus-lg me-1"></i>Buat Bank Soal
        </Button>
    {/snippet}
</PageHeader>

{#if kelompoks.data.length === 0}
    <EmptyState message="Belum ada bank soal" hint="Klik 'Buat Bank Soal' untuk membuat wadah soal pertama." />
{:else}
    <div class="row g-2 g-md-3">
        {#each kelompoks.data as k}
            <div class="col-12 col-md-6 col-lg-4">
                <Card class="h-100">
                    <CardBody>
                        <a use:inertia href="/app/guru/bank-soal/kelompok/{k.id}" class="text-decoration-none text-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-semibold mb-0">{k.nama}</h6>
                                <span class="badge bg-primary rounded-pill">{k.soals_count} soal</span>
                            </div>
                            {#if k.matpel}<div class="text-muted small mb-1">{k.matpel.name}</div>{/if}
                            {#if k.deskripsi}<p class="text-muted small mb-0">{k.deskripsi}</p>{/if}
                        </a>
                        <div class="d-flex gap-1 mt-2">
                            <Button size="sm" color="outline-primary" onclick={(e) => bukaEdit(k, e)}>
                                <i class="bi bi-pencil"></i> Edit
                            </Button>
                            {#if konfirmHapusId === k.id}
                                <Button size="sm" color="danger" onclick={(e) => hapus(k, e)}>
                                    <i class="bi bi-check-lg"></i> Ya
                                </Button>
                                <Button size="sm" color="outline-secondary" onclick={(e) => { e.preventDefault(); e.stopPropagation(); konfirmHapusId = null; }}>
                                    Batal
                                </Button>
                            {:else}
                                <Button size="sm" color="outline-danger" onclick={(e) => { e.preventDefault(); e.stopPropagation(); konfirmHapusId = k.id; }}>
                                    <i class="bi bi-trash"></i> Hapus
                                </Button>
                            {/if}
                        </div>
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
{/if}

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
    <ModalHeader toggle={() => (modalOpen = false)}>
        <i class="bi bi-plus-circle me-2"></i>{editId ? 'Edit' : 'Tambah'} banksoal
    </ModalHeader>
    <ModalBody style="max-height: 70vh; overflow-y: auto;">
        <!-- Pengaturan umum -->
        <div class="border rounded p-3 mb-3 bg-light">
            <h6 class="fw-bold mb-3">Pengaturan umum</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="bk-nama">Nama Bank Soal <span class="text-danger">*</span></label>
                <input id="bk-nama" class="form-control" bind:value={form.nama}
                    placeholder="cth: Bank Soal UTS Matematika 2026" />
                {#if form.errors.nama}<div class="text-danger small">{form.errors.nama}</div>{/if}
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="bk-kode">Kode banksoal</label>
                <input id="bk-kode" class="form-control" bind:value={form.kode}
                    placeholder="Kode banksoal" />
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="bk-matpel">Mata pelajaran <span class="text-danger">*</span></label>
                <select id="bk-matpel" class="form-select" bind:value={form.matpel_id}>
                    <option value={null}>Pilih mata pelajaran</option>
                    {#each matpelOptions as m}<option value={m.value}>{m.label}</option>{/each}
                </select>
                {#if form.errors.matpel_id}<div class="text-danger small">{form.errors.matpel_id}</div>{/if}
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="bk-urutan">Urutan tampil</label>
                <select id="bk-urutan" class="form-select" bind:value={form.urutan_tampil}>
                    <option value="tipe_waktu">Tipe soal & waktu pembuatan</option>
                    <option value="manual">Pengurutan manual</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="bk-deskripsi">Deskripsi (opsional)</label>
                <textarea id="bk-deskripsi" class="form-control" rows="2" bind:value={form.deskripsi}></textarea>
            </div>
        </div>

    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
        <Button color="primary" onclick={simpanEdit} disabled={form.processing || !form.nama}>
            Simpan
        </Button>
    </ModalFooter>
</Modal>
