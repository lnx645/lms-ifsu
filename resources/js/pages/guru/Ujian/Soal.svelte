<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { inertia } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Dropdown,
        DropdownItem,
        DropdownMenu,
        DropdownToggle,
        Input,
        Modal,
        ModalBody,
        ModalFooter,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import { confirm } from '@/lib/confirm.svelte';
    import SoalRenderer from '@/components/soal/render/SoalRenderer.svelte';
    import Latex from '@/components/Latex.svelte';
    import {
        KESULITAN_INFO,
        TIPE_SOAL_INFO,
        labelTipeSoal,
        type Kesulitan,
        type TipeSoal,
    } from '@/lib/ujian';
    import SoalController from '@/actions/App/Http/Controllers/Guru/SoalController';
    import UjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import AkademikController from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';
    import RichTextEditor from '@/components/RichTextEditor.svelte';
    import 'katex/dist/katex.min.css';

    type Opsi = { id?: number; teks: string; benar: boolean };
    type BankItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        topik: string | null;
        kesulitan: Kesulitan;
        jumlah_opsi: number;
        kelompok_id: number | null;
    };
    type SoalItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        urutan: number;
        kunci_isian: string[] | null;
        isian_case_sensitive: boolean;
        kode_kd: string | null;
        indikator: string | null;
        media_path: string | null;
        media_type: string | null;
        media_url: string | null;
        opsi: { id: number; teks: string; benar: boolean }[];
    };

    let {
        ujian,
        soals,
        pakets = [],
        bank = [],
        bankKelompok = [],
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: string;
            status: string;
            kelas: string | null;
            matpel: string | null;
            matpel_id: number | null;
            nilai_maks: number;
            total_poin: number;
            memakai_paket: boolean;
        };
        soals: SoalItem[];
        pakets: { id: number; kode: string; nama: string; soals: SoalItem[] }[];
        bank: BankItem[];
        bankKelompok: { id: number; nama: string; jumlah: number }[];
    } = $props();

    // Tab paket aktif (untuk ujian resmi)
    let paketAktif = $state<number | null>(pakets[0]?.id ?? null);

    const soalTampil = $derived(
        ujian.memakai_paket && paketAktif
            ? (pakets.find((p) => p.id === paketAktif)?.soals ?? [])
            : soals,
    );

    const tipeOptions = (Object.keys(TIPE_SOAL_INFO) as TipeSoal[]).map((t) => ({
        value: t,
        label: labelTipeSoal(t),
    }));

    const form = useForm({
        tipe: 'pg' as TipeSoal,
        pertanyaan: '',
        poin: 1,
        paket_soal_id: null as number | null,
        opsi: [
            { teks: '', benar: true },
            { teks: '', benar: false },
        ] as Opsi[],
        kunci_isian: [''] as string[],
        isian_case_sensitive: false,
        kode_kd: '',
        indikator: '',
    });

    let modalOpen = $state(false);
    let editId = $state<number | null>(null);

    function resetForm() {
        form.reset();
        form.opsi = [
            { teks: '', benar: true },
            { teks: '', benar: false },
        ];
        form.kunci_isian = [''];
        form.clearErrors();
    }

    function bukaBuat() {
        resetForm();
        editId = null;
        // Otomatis masuk ke paket yang sedang aktif
        if (ujian.memakai_paket && paketAktif) {
            form.paket_soal_id = paketAktif;
        }
        modalOpen = true;
    }

    function bukaEdit(s: SoalItem) {
        editId = s.id;
        form.tipe = s.tipe;
        form.pertanyaan = s.pertanyaan;
        form.poin = s.poin;
        form.opsi = s.opsi.length
            ? s.opsi.map((o) => ({ teks: o.teks, benar: o.benar }))
            : [
                  { teks: '', benar: true },
                  { teks: '', benar: false },
              ];
        form.kunci_isian = s.kunci_isian?.length ? [...s.kunci_isian] : [''];
        form.isian_case_sensitive = s.isian_case_sensitive;
        form.kode_kd = s.kode_kd ?? '';
        form.indikator = s.indikator ?? '';
        form.clearErrors();
        modalOpen = true;
    }

    function gantiTipe(t: TipeSoal) {
        form.tipe = t;
        if (t === 'benar_salah') {
            form.opsi = [
                { teks: 'Benar', benar: true },
                { teks: 'Salah', benar: false },
            ];
        }
    }

    function tambahOpsi() {
        form.opsi = [...form.opsi, { teks: '', benar: false }];
    }
    function hapusOpsi(i: number) {
        form.opsi = form.opsi.filter((_, idx) => idx !== i);
    }
    function setBenarTunggal(i: number) {
        form.opsi = form.opsi.map((o, idx) => ({ ...o, benar: idx === i }));
    }
    function tambahKunci() {
        form.kunci_isian = [...form.kunci_isian, ''];
    }
    function hapusKunci(i: number) {
        form.kunci_isian = form.kunci_isian.filter((_, idx) => idx !== i);
    }

    function simpan() {
        const onSuccess = () => {
            modalOpen = false;
            resetForm();
        };
        const butuhOpsiTipe = ['pg', 'multi', 'benar_salah'].includes(
            form.tipe,
        );
        form.transform((data) => ({
            tipe: data.tipe,
            pertanyaan: data.pertanyaan,
            poin: data.poin,
            opsi: butuhOpsiTipe ? data.opsi : [],
            kunci_isian: data.tipe === 'isian' ? data.kunci_isian : [],
            isian_case_sensitive: data.isian_case_sensitive,
        }));
        if (editId) {
            form.put(SoalController.update({ ujian: ujian.id, soal: editId }).url, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post(SoalController.store({ ujian: ujian.id }).url, {
                preserveScroll: true,
                onSuccess,
            });
        }
    }

    async function hapus(s: SoalItem) {
        const ok = await confirm.show({
            title: 'Hapus Soal',
            message: 'Soal ini akan dihapus. Lanjutkan?',
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(SoalController.destroy({ ujian: ujian.id, soal: s.id }).url, {
            preserveScroll: true,
        });
    }

    const butuhOpsi = $derived(['pg', 'multi', 'benar_salah'].includes(form.tipe));

    // ---- Bank soal ----
    let bankModalOpen = $state(false);
    let bankKelompokAktif = $state<number | null>(null);
    let poolModalOpen = $state(false);
    let poolConfig = $state<Record<number, number>>({});
    let poolAktif = $state(false);

    const bankSoalKelompok = $derived(
        bankKelompokAktif === null
            ? []
            : bank.filter((b) => b.kelompok_id === bankKelompokAktif),
    );
    let generateModalOpen = $state(false);
    let importModalOpen = $state(false);
    let importFile: FileList | undefined = $state();

    function kirimImport(e: SubmitEvent) {
        e.preventDefault();
        if (!importFile?.[0]) return;
        router.post(
            AkademikController.importStore({ ujian: ujian.id }).url,
            { file: importFile[0] },
            { forceFormData: true, onSuccess: () => { importModalOpen = false; importFile = undefined; } },
        );
    }

    function unggahMedia(s: SoalItem, e: Event) {
        const input = e.target as HTMLInputElement;
        const file = input.files?.[0];
        if (!file) return;
        router.post(
            SoalController.uploadMedia({ ujian: ujian.id, soal: s.id }).url,
            { media: file },
            { forceFormData: true },
        );
        input.value = '';
    }

    async function hapusMedia(s: SoalItem) {
        const ok = await confirm.show({
            title: 'Hapus Media',
            message: 'Hapus lampiran media soal ini?',
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(SoalController.hapusMedia({ ujian: ujian.id, soal: s.id }).url);
    }
    let simpanBankOpen = $state(false);
    let simpanBankSoal = $state<SoalItem | null>(null);
    let bankTerpilih = $state<number[]>([]);

    const ambilForm = useForm({ bank_soal_ids: [] as number[] });
    const generateForm = useForm({
        jumlah: 5,
        tipe: '' as '' | TipeSoal,
        topik: '',
        kesulitan: '' as '' | Kesulitan,
    });
    const simpanForm = useForm({ topik: '', kesulitan: 'sedang' as Kesulitan });

    function toggleBank(id: number) {
        bankTerpilih = bankTerpilih.includes(id)
            ? bankTerpilih.filter((x) => x !== id)
            : [...bankTerpilih, id];
    }

    function ambilDariBank() {
        ambilForm.bank_soal_ids = bankTerpilih;
        ambilForm.post(SoalController.ambilDariBank({ ujian: ujian.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                bankModalOpen = false;
                bankTerpilih = [];
            },
        });
    }

    function simpanPool() {
        const pool = Object.entries(poolConfig).map(([kelompokId, jumlah]) => ({
            kelompok_bank_soal_id: Number(kelompokId),
            jumlah: Number(jumlah),
        }));
        router.post(`/app/guru/ujian/${ujian.id}/pool`, { pool }, {
            preserveScroll: true,
            onSuccess: () => {
                poolModalOpen = false;
                poolAktif = pool.length > 0;
            },
        });
    }

    function generate() {
        generateForm
            .transform((d) => ({
                jumlah: d.jumlah,
                tipe: d.tipe || null,
                topik: d.topik || null,
                kesulitan: d.kesulitan || null,
            }))
            .post(SoalController.generateDariBank({ ujian: ujian.id }).url, {
                preserveScroll: true,
                onSuccess: () => (generateModalOpen = false),
            });
    }

    function bukaSimpanBank(s: SoalItem) {
        simpanBankSoal = s;
        simpanForm.reset();
        simpanForm.clearErrors();
        simpanBankOpen = true;
    }

    function simpanKeBank() {
        if (!simpanBankSoal) return;
        simpanForm.post(
            SoalController.simpanKeBank({ ujian: ujian.id, soal: simpanBankSoal.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => (simpanBankOpen = false),
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <div class="mb-3 d-flex gap-2">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Daftar Ujian
        </a>

    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">{ujian.judul}</h1>
            <div class="text-muted small">
                {ujian.kelas} · {ujian.matpel} · Total poin: {ujian.total_poin}
            </div>
        </div>
        <div class="d-flex gap-2">
            <Button color="primary" onclick={() => (bankModalOpen = true)}>
                <i class="bi bi-collection me-1"></i>Ambil dari Bank Soal
            </Button>
            <Button color="outline-primary" onclick={() => (poolModalOpen = true)}>
                <i class="bi bi-shuffle me-1"></i>Paket Otomatis
            </Button>
        </div>
    </div>

    {#if ujian.memakai_paket && pakets.length > 0}
        <div class="mb-3">
            <div class="btn-group" role="group">
                {#each pakets as p}
                    <button
                        type="button"
                        class="btn {paketAktif === p.id ? 'btn-primary' : 'btn-outline-primary'}"
                        onclick={() => (paketAktif = p.id)}
                    >
                        <i class="bi bi-folder me-1"></i>{p.kode} — {p.nama}
                        <span class="badge {paketAktif === p.id ? 'text-bg-light' : 'text-bg-primary'} ms-1">{p.soals.length}</span>
                    </button>
                {/each}
            </div>
            <div class="form-text mt-1">
                <i class="bi bi-info-circle me-1"></i>Soal dikelola per paket. Pilih paket di atas, lalu tambah/edit soal untuk paket tersebut.
            </div>
        </div>
    {/if}

    <div class="alert alert-light border small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Soal diambil dari <strong>Bank Soal</strong>. Kelola soal di menu Bank Soal, lalu pilih di sini.
    </div>

    {#if soalTampil.length === 0}
        <div class="text-center text-muted py-5">
            <i class="bi bi-card-list display-5 d-block mb-2"></i>
            <div>Belum ada soal. Tambahkan soal untuk ujian ini.</div>
        </div>
    {:else}
        {#each soalTampil as s, i (s.id)}
            <Card class="border mb-3">
                <CardBody class="p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="badge bg-primary rounded-pill">{i + 1}</span>
                                <Badge color="light" class="text-dark border">
                                    <i class={`bi ${TIPE_SOAL_INFO[s.tipe]?.icon ?? 'bi-question-circle'} me-1`}></i>
                                    {labelTipeSoal(s.tipe)}
                                </Badge>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{s.poin} poin</span>
                                {#if s.bank_soal_id}
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" title="Dari Bank Soal">
                                        <i class="bi bi-collection me-1"></i>Bank
                                    </span>
                                {/if}
                            </div>
                            <div class="mb-2 fw-medium"><Latex teks={s.pertanyaan} /></div>
                            {#if s.kode_kd}
                                <div class="small text-muted mt-1"><i class="bi bi-tag me-1"></i>{s.kode_kd}{s.indikator ? ` — ${s.indikator}` : ''}</div>
                            {/if}
                            {#if s.media_url}
                                <div class="mt-2">
                                    {#if s.media_type === 'gambar'}
                                        <img src={s.media_url} alt="Media soal" style="max-width:280px;max-height:180px;border-radius:6px;border:1px solid #ddd" />
                                    {:else}
                                        <audio controls src={s.media_url} style="max-width:280px"></audio>
                                    {/if}
                                    <div>
                                        <Button size="sm" color="link" class="text-danger p-0 small" onclick={() => hapusMedia(s)}>Hapus media</Button>
                                    </div>
                                </div>
                            {/if}
                            <div class="mt-2">
                                {#key s.id}
                                    {@const dd = s.detail ?? (
                                        s.tipe === 'isian_singkat' && s.kunci_isian?.length ? { kunci: s.kunci_isian } :
                                        s.opsi?.length ? { opsi: s.opsi } :
                                        null
                                    )}
                                    <SoalRenderer
                                        tipe={s.tipe}
                                        detail={dd}
                                        jawaban={null}
                                        disabled={true}
                                        showKunci={true}
                                    />
                                {/key}
                            </div>
                        </div>
                        <div class="d-inline-flex gap-1 align-items-start">
                            <Button size="sm" color="outline-danger" onclick={() => hapus(s)} title="Hapus dari ujian">
                                <i class="bi bi-trash"></i>
                            </Button>
                        </div>
                    </div>
                </CardBody>
            </Card>
        {/each}
    {/if}

    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>
            {editId ? 'Edit Soal' : 'Tambah Soal'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="s-tipe">Tipe Soal</label>
                    <select
                        id="s-tipe"
                        class="form-select"
                        value={form.tipe}
                        onchange={(e) => gantiTipe((e.currentTarget as HTMLSelectElement).value as TipeSoal)}
                    >
                        {#each tipeOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="s-poin">Poin</label>
                    <input id="s-poin" type="number" min="1" class="form-control" bind:value={form.poin} />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="s-kd">Kode KD</label>
                    <input id="s-kd" type="text" class="form-control" placeholder="mis. KD 3.1" bind:value={form.kode_kd} />
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="s-indikator">Indikator</label>
                    <input id="s-indikator" type="text" class="form-control" placeholder="Indikator pencapaian..." bind:value={form.indikator} />
                </div>
                <div class="col-12">
                    <label class="form-label" for="s-pertanyaan">Pertanyaan</label>
                    {#key editId ?? 'baru'}
                        <RichTextEditor
                            id="s-pertanyaan"
                            value={form.pertanyaan}
                            placeholder="Tulis pertanyaan… (tombol fx untuk rumus matematika)"
                            invalid={!!form.errors.pertanyaan}
                            onchange={(html) => (form.pertanyaan = html)}
                        />
                    {/key}
                    {#if form.errors.pertanyaan}
                        <div class="text-danger small">{form.errors.pertanyaan}</div>
                    {/if}
                </div>

                {#if butuhOpsi}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">
                                Opsi Jawaban
                                <span class="text-muted fw-normal small">
                                    {form.tipe === 'multi'
                                        ? '(centang semua jawaban benar)'
                                        : '(pilih satu jawaban benar)'}
                                </span>
                            </span>
                            {#if form.tipe !== 'benar_salah'}
                                <Button size="sm" color="outline-primary" onclick={tambahOpsi}>
                                    <i class="bi bi-plus"></i> Tambah Opsi
                                </Button>
                            {/if}
                        </div>
                        {#each form.opsi as opsi, i (i)}
                            <div class="input-group mb-2">
                                <span
                                    class="input-group-text bg-light fw-bold text-secondary"
                                    style="min-width: 48px; text-align: center;"
                                >
                                    {String.fromCharCode(65 + i)}
                                </span>
                                <span class="input-group-text">
                                    {#if form.tipe === 'multi'}
                                        <input
                                            type="checkbox"
                                            checked={opsi.benar}
                                            onchange={(e) => (form.opsi[i].benar = (e.currentTarget as HTMLInputElement).checked)}
                                            class="form-check-input mt-0"
                                            aria-label="Jawaban benar"
                                        />
                                    {:else}
                                        <input
                                            type="radio"
                                            name="opsi-benar"
                                            checked={opsi.benar}
                                            onchange={() => setBenarTunggal(i)}
                                            class="form-check-input mt-0"
                                            aria-label="Jawaban benar"
                                        />
                                    {/if}
                                </span>
                                <input
                                    class="form-control"
                                    placeholder={`Isi teks opsi ${String.fromCharCode(65 + i)}`}
                                    bind:value={form.opsi[i].teks}
                                    readonly={form.tipe === 'benar_salah'}
                                />
                                {#if form.tipe !== 'benar_salah' && form.opsi.length > 2}
                                    <Button color="outline-danger" onclick={() => hapusOpsi(i)} class="ms-1" title="Hapus opsi">
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        {#if form.errors.opsi}
                            <div class="text-danger small">{form.errors.opsi}</div>
                        {/if}
                    </div>
                {:else if form.tipe === 'isian'}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Kunci Jawaban (salah satu cocok = benar)</span>
                            <Button size="sm" color="outline-primary" onclick={tambahKunci}>
                                <i class="bi bi-plus"></i> Kunci
                            </Button>
                        </div>
                        {#each form.kunci_isian as _, i (i)}
                            <div class="input-group mb-2">
                                <input class="form-control" bind:value={form.kunci_isian[i]} placeholder={`Kunci ${i + 1}`} />
                                {#if form.kunci_isian.length > 1}
                                    <Button color="outline-danger" onclick={() => hapusKunci(i)}>
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="s-case" bind:checked={form.isian_case_sensitive} />
                            <label class="form-check-label" for="s-case">Perhatikan huruf besar/kecil</label>
                        </div>
                        {#if form.errors.kunci_isian}
                            <div class="text-danger small">{form.errors.kunci_isian}</div>
                        {/if}
                    </div>
                {:else}
                    <div class="col-12">
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Soal esai dinilai manual oleh guru setelah ujian.
                        </div>
                    </div>
                {/if}
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpan} disabled={form.processing}>Simpan</Button>
        </ModalFooter>
    </Modal>

    <!-- Ambil dari Bank -->
    <Modal isOpen={bankModalOpen} toggle={() => (bankModalOpen = !bankModalOpen)} size="lg">
        <ModalHeader toggle={() => { bankModalOpen = false; bankKelompokAktif = null; }}>Ambil Soal dari Bank</ModalHeader>
        <ModalBody>
            {#if bank.length === 0}
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inboxes display-6 d-block mb-2"></i>
                    Belum ada soal bank untuk mata pelajaran ini.
                </div>
            {:else if !(bankKelompok.length)}
                <div class="small text-muted mb-2">
                    {bankTerpilih.length} soal dipilih. Soal akan disalin (snapshot) ke ujian.
                </div>
                {#each bank as b (b.id)}
                    <label class="d-flex align-items-start gap-2 border rounded-1 p-2 mb-2">
                        <input
                            type="checkbox"
                            class="mt-1"
                            checked={bankTerpilih.includes(b.id)}
                            onchange={() => toggleBank(b.id)}
                        />
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <Badge color="light" class="text-dark border">{labelTipeSoal(b.tipe)}</Badge>
                                <Badge color={KESULITAN_INFO[b.kesulitan].color} pill>{KESULITAN_INFO[b.kesulitan].label}</Badge>
                                <span class="text-muted small">{b.poin} poin</span>
                                {#if b.topik}<span class="badge bg-secondary-subtle text-secondary-emphasis">{b.topik}</span>{/if}
                            </div>
                            <div class="rich-deskripsi small">{@html b.pertanyaan}</div>
                        </div>
                    </label>
                {/each}
            {:else if bankKelompokAktif === null}
                <div class="small text-muted mb-3">
                    Pilih kelompok bank soal:
                </div>
                <div class="list-group">
                    {#each bankKelompok as k}
                        <button
                            type="button"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                            onclick={() => (bankKelompokAktif = k.id)}
                        >
                            <span><i class="bi bi-folder2 me-2"></i>{k.nama}</span>
                            <span class="badge bg-primary rounded-pill">{k.jumlah} soal</span>
                        </button>
                    {/each}
                </div>
            {:else}
                <div class="d-flex align-items-center gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => (bankKelompokAktif = null)}>
                        <i class="bi bi-arrow-left"></i> Kelompok
                    </button>
                    <div class="small text-muted">
                        {bankTerpilih.length} soal dipilih.
                    </div>
                </div>
                {#each bankSoalKelompok as b (b.id)}
                    <label class="d-flex align-items-start gap-2 border rounded-1 p-2 mb-2">
                        <input
                            type="checkbox"
                            class="mt-1"
                            checked={bankTerpilih.includes(b.id)}
                            onchange={() => toggleBank(b.id)}
                        />
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <Badge color="light" class="text-dark border">{labelTipeSoal(b.tipe)}</Badge>
                                <Badge color={KESULITAN_INFO[b.kesulitan].color} pill>{KESULITAN_INFO[b.kesulitan].label}</Badge>
                                <span class="text-muted small">{b.poin} poin</span>
                                {#if b.topik}<span class="badge bg-secondary-subtle text-secondary-emphasis">{b.topik}</span>{/if}
                            </div>
                            <div class="rich-deskripsi small">{@html b.pertanyaan}</div>
                        </div>
                    </label>
                {/each}
            {/if}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => { bankModalOpen = false; bankKelompokAktif = null; }}>Batal</Button>
            <Button color="primary" onclick={ambilDariBank} disabled={ambilForm.processing || bankTerpilih.length === 0}>
                Salin {bankTerpilih.length} Soal
            </Button>
        </ModalFooter>
    </Modal>

    <!-- Paket Otomatis -->
    <Modal isOpen={poolModalOpen} toggle={() => (poolModalOpen = !poolModalOpen)} size="lg">
        <ModalHeader toggle={() => (poolModalOpen = false)}>Paket Soal Otomatis</ModalHeader>
        <ModalBody>
            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Pilih kelompok bank soal dan tentukan jumlah soal yang diambil acak untuk tiap siswa.
                Tiap siswa akan mendapat kombinasi soal yang berbeda.
            </div>
            {#if (bankKelompok.length ?? 0) === 0}
                <div class="text-center text-muted py-4">Belum ada kelompok bank soal.</div>
            {:else}
                {#each bankKelompok as k}
                    <div class="d-flex align-items-center gap-3 border rounded-2 p-3 mb-2">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            checked={poolConfig[k.id] !== undefined}
                            onchange={(e) => {
                                if (e.currentTarget.checked) {
                                    poolConfig[k.id] = Math.min(10, k.jumlah);
                                } else {
                                    delete poolConfig[k.id];
                                }
                            }}
                        />
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{k.nama}</div>
                            <div class="text-muted small">{k.jumlah} soal tersedia</div>
                        </div>
                        {#if poolConfig[k.id] !== undefined}
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">Ambil</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    style="width: 80px"
                                    min="1"
                                    max={k.jumlah}
                                    bind:value={poolConfig[k.id]}
                                />
                                <span class="small text-muted">soal</span>
                            </div>
                        {/if}
                    </div>
                {/each}
            {/if}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (poolModalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpanPool} disabled={Object.keys(poolConfig).length === 0}>
                Simpan Paket
            </Button>
        </ModalFooter>
    </Modal>

    <!-- Generate Acak -->
    <Modal isOpen={generateModalOpen} toggle={() => (generateModalOpen = !generateModalOpen)}>
        <ModalHeader toggle={() => (generateModalOpen = false)}>Generate Acak dari Bank</ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label" for="g-jumlah">Jumlah Soal</label>
                    <input id="g-jumlah" type="number" min="1" class="form-control" bind:value={generateForm.jumlah} />
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-tipe">Tipe (opsional)</label>
                    <select id="g-tipe" class="form-select" bind:value={generateForm.tipe}>
                        <option value="">Semua</option>
                        {#each Object.keys(TIPE_SOAL_INFO) as t (t)}
                            <option value={t}>{labelTipeSoal(t)}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-topik">Topik (opsional)</label>
                    <input id="g-topik" class="form-control" bind:value={generateForm.topik} />
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-kesulitan">Kesulitan (opsional)</label>
                    <select id="g-kesulitan" class="form-select" bind:value={generateForm.kesulitan}>
                        <option value="">Semua</option>
                        {#each Object.keys(KESULITAN_INFO) as k (k)}
                            <option value={k}>{KESULITAN_INFO[k as Kesulitan].label}</option>
                        {/each}
                    </select>
                </div>
            </div>
            <div class="form-text mt-2">
                Sistem mengambil soal acak dari bank matpel ini sesuai kriteria, lalu menyalinnya ke ujian.
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (generateModalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={generate} disabled={generateForm.processing}>Generate</Button>
        </ModalFooter>
    </Modal>

    <!-- Simpan ke Bank -->
    <Modal isOpen={simpanBankOpen} toggle={() => (simpanBankOpen = !simpanBankOpen)}>
        <ModalHeader toggle={() => (simpanBankOpen = false)}>Simpan Soal ke Bank</ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="sb-topik">Topik (opsional)</label>
                    <input id="sb-topik" class="form-control" bind:value={simpanForm.topik} placeholder="mis. Aljabar" />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sb-kesulitan">Kesulitan</label>
                    <select id="sb-kesulitan" class="form-select" bind:value={simpanForm.kesulitan}>
                        {#each Object.keys(KESULITAN_INFO) as k (k)}
                            <option value={k}>{KESULITAN_INFO[k as Kesulitan].label}</option>
                        {/each}
                    </select>
                </div>
            </div>
            <div class="form-text mt-2">Soal disalin ke bank mata pelajaran ujian ini.</div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (simpanBankOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpanKeBank} disabled={simpanForm.processing}>Simpan ke Bank</Button>
        </ModalFooter>
    </Modal>

    <Modal isOpen={importModalOpen} toggle={() => (importModalOpen = false)}>
        <ModalHeader>Import Soal dari Excel</ModalHeader>
        <form onsubmit={kirimImport}>
            <ModalBody>
                <p class="small text-muted">Unduh template Excel terlebih dahulu, isi sesuai format, lalu unggah file .xlsx (maks 5 MB).</p>
                <Input type="file" accept=".xlsx" bind:files={importFile} />
            </ModalBody>
            <ModalFooter>
                <Button color="secondary" onclick={() => (importModalOpen = false)}>Batal</Button>
                <Button color="success" type="submit" disabled={!importFile?.[0]}>Impor</Button>
            </ModalFooter>
        </form>
    </Modal>
</div>
