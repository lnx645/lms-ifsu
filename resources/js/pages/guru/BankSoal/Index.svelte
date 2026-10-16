<script lang="ts">
    import { router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Modal,
        ModalBody,
        ModalFooter,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import { extractId } from '@/lib/utils';
    import {
        KESULITAN_INFO,
        TIPE_SOAL_INFO,
        type Kesulitan,
        type TipeSoal,
    } from '@/lib/ujian';
    import BankSoalController from '@/actions/App/Http/Controllers/Guru/BankSoalController';
    import type { PaginationMeta } from '@/types/models';
    import RichTextEditor from '@/components/RichTextEditor.svelte';
    import 'katex/dist/katex.min.css';

    type Opsi = { id: number; teks: string; benar: boolean };
    type BankItem = {
        id: number;
        matpel_id: number;
        matpel: string | null;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        topik: string | null;
        kesulitan: Kesulitan;
        kunci_isian: string[] | null;
        isian_case_sensitive: boolean;
        jumlah_opsi: number;
        opsi: Opsi[];
        milik_saya: boolean;
    };
    type MatpelOption = { value: number; label: string };

    const HURUF = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    const huruf = (i: number): string => HURUF[i] ?? `${i + 1}`;

    let {
        bank,
        matpelOptions = [],
        filters = { matpel_id: null, tipe: null, kesulitan: null, topik: '', q: '' },
    }: {
        bank: PaginationMeta & { data: BankItem[] };
        matpelOptions: MatpelOption[];
        filters: {
            matpel_id: number | null;
            tipe: TipeSoal | null;
            kesulitan: Kesulitan | null;
            topik: string;
            q: string;
        };
    } = $props();

    const tipeOptions = (Object.keys(TIPE_SOAL_INFO) as TipeSoal[]).map((t) => ({
        value: t,
        label: TIPE_SOAL_INFO[t].label,
    }));
    const kesulitanOptions = (Object.keys(KESULITAN_INFO) as Kesulitan[]).map((k) => ({
        value: k,
        label: KESULITAN_INFO[k].label,
    }));

    const defaultOpsi = () => [
        { teks: '', benar: true },
        { teks: '', benar: false },
    ];

    function baseForm() {
        return {
            matpel_id: (matpelOptions[0]?.value ?? null) as number | null,
            tipe: 'pg' as TipeSoal,
            pertanyaan: '',
            poin: 10,
            topik: '',
            kesulitan: 'sedang' as Kesulitan,
            opsi: defaultOpsi(),
            kunci_isian: [''] as string[],
            isian_case_sensitive: false,
        };
    }

    const form = useForm(baseForm());

    // svelte-ignore state_referenced_locally
    let filterMatpel = $state(filters.matpel_id);
    // svelte-ignore state_referenced_locally
    let filterTipe: TipeSoal | null = $state(filters.tipe);
    // svelte-ignore state_referenced_locally
    let filterKesulitan: Kesulitan | null = $state(filters.kesulitan);
    // svelte-ignore state_referenced_locally
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let modalOpen = $state(false);
    let editId = $state<number | null>(null);

    const butuhOpsi = $derived(['pg', 'multi', 'benar_salah'].includes(form.tipe));

    function resetForm() {
        form.reset();
        form.opsi = defaultOpsi();
        form.kunci_isian = [''];
        form.clearErrors();
    }

    function bukaBuat() {
        resetForm();
        editId = null;
        modalOpen = true;
    }

    function bukaEdit(b: BankItem) {
        editId = b.id;
        form.matpel_id = b.matpel_id;
        form.tipe = b.tipe;
        form.pertanyaan = b.pertanyaan;
        form.poin = b.poin;
        form.topik = b.topik ?? '';
        form.kesulitan = b.kesulitan;
        form.opsi = b.opsi.length ? b.opsi.map((o) => ({ teks: o.teks, benar: o.benar })) : defaultOpsi();
        form.kunci_isian = b.kunci_isian?.length ? [...b.kunci_isian] : [''];
        form.isian_case_sensitive = b.isian_case_sensitive;
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
        } else if (['pg', 'multi'].includes(t)) {
            form.opsi = defaultOpsi();
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
        form.transform((data) => ({
            matpel_id: data.matpel_id,
            tipe: data.tipe,
            pertanyaan: data.pertanyaan,
            poin: data.poin,
            topik: data.topik || null,
            kesulitan: data.kesulitan,
            opsi: ['pg', 'multi', 'benar_salah'].includes(data.tipe) ? data.opsi : [],
            kunci_isian: data.tipe === 'isian' ? data.kunci_isian : [],
            isian_case_sensitive: data.isian_case_sensitive,
        }));
        if (editId) {
            form.put(BankSoalController.update({ bankSoal: editId }).url, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post(BankSoalController.store().url, {
                preserveScroll: true,
                onSuccess,
            });
        }
    }

    async function hapus(b: BankItem) {
        const ok = await confirm.show({
            title: 'Hapus Soal Bank',
            message: 'Soal ini akan dihapus dari bank. Ujian yang sudah memakai salinannya tidak terpengaruh. Lanjutkan?',
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(BankSoalController.destroy({ bankSoal: b.id }).url, {
            preserveScroll: true,
        });
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                matpel_id: filterMatpel ?? undefined,
                tipe: filterTipe ?? undefined,
                kesulitan: filterKesulitan ?? undefined,
                q: searchInput.trim() || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['bank', 'filters'] },
        );
    }

    function resetFilter() {
        filterMatpel = null;
        filterTipe = null;
        filterKesulitan = null;
        searchInput = '';
        reload();
    }

    function onSearchInput() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 400);
    }

    function goToPage(page: number) {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                page,
                matpel_id: filterMatpel ?? undefined,
                tipe: filterTipe ?? undefined,
                kesulitan: filterKesulitan ?? undefined,
                q: searchInput.trim() || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['bank'] },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Bank Soal"
        subtitle="Kumpulan soal per mata pelajaran yang bisa dipakai ulang di berbagai ujian."
    >
        {#snippet actions()}
            <Button color="primary" onclick={bukaBuat} disabled={matpelOptions.length === 0}>
                <i class="bi bi-plus-lg me-1"></i>Tambah Soal
            </Button>
        {/snippet}
    </PageHeader>

    {#if matpelOptions.length === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-info-circle display-6 d-block mb-2"></i>
                Anda belum memiliki penugasan mata pelajaran aktif tahun ini.
            </CardBody>
        </Card>
    {:else}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="p-3">
                <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                    <div style="min-width: 200px">
                        <Select
                            id="f-matpel"
                            items={matpelOptions}
                            value={filterMatpel}
                            placeholder="Semua matpel"
                            clearable={true}
                            getOptionValue={(item) => item.value}
                            onchange={(v) => {
                                filterMatpel = extractId(v);
                                reload();
                            }}
                        />
                    </div>
                    <div style="min-width: 150px">
                        <Select
                            id="f-tipe"
                            items={tipeOptions}
                            value={filterTipe}
                            placeholder="Semua tipe"
                            clearable={true}
                            getOptionValue={(item) => item.value}
                            onchange={(v) => {
                                filterTipe = extractId(v) as TipeSoal | null;
                                reload();
                            }}
                        />
                    </div>
                    <div style="min-width: 140px">
                        <Select
                            id="f-kesulitan"
                            items={kesulitanOptions}
                            value={filterKesulitan}
                            placeholder="Semua tingkat"
                            clearable={true}
                            getOptionValue={(item) => item.value}
                            onchange={(v) => {
                                filterKesulitan = extractId(v) as Kesulitan | null;
                                reload();
                            }}
                        />
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 260px">
                        <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                        <input
                            type="search"
                            class="form-control"
                            placeholder="Cari pertanyaan…"
                            bind:value={searchInput}
                            onkeyup={onSearchInput}
                        />
                    </div>
                    {#if filterMatpel || filterTipe || filterKesulitan || searchInput}
                        <Button size="sm" color="outline-secondary" onclick={resetFilter}>
                            <i class="bi bi-x-lg me-1"></i>Reset
                        </Button>
                    {/if}
                </div>

                {#if bank.data.length === 0}
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inboxes display-5 d-block mb-2"></i>
                        <div>Belum ada soal di bank. Klik "Tambah Soal".</div>
                    </div>
                {:else}
                    {#each bank.data as b, no (b.id)}
                        <div class="border rounded-1 p-3 mb-2 bg-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-1">#</span>
                                        <Badge color="light" class="text-dark border">
                                            <i class={`bi ${TIPE_SOAL_INFO[b.tipe]?.icon ?? 'bi-question-circle'} me-1`}></i>
                                            {TIPE_SOAL_INFO[b.tipe].label}
                                        </Badge>
                                        <Badge color={KESULITAN_INFO[b.kesulitan].color} pill>
                                            {KESULITAN_INFO[b.kesulitan].label}
                                        </Badge>
                                        {#if b.matpel}
                                            <span class="text-muted small">{b.matpel}</span>
                                        {/if}
                                        <span class="text-muted small">· {b.poin} poin</span>
                                        {#if b.topik}
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                <i class="bi bi-tag me-1"></i>{b.topik}
                                            </span>
                                        {/if}
                                    </div>
                                    <div class="rich-deskripsi">{@html b.pertanyaan}</div>
                                    {#if b.opsi.length}
                                        <div class="mt-2 d-flex flex-column gap-1">
                                            {#each b.opsi as o, i (o.id)}
                                                <div
                                                    class={`d-flex align-items-start gap-2 small rounded-1 px-2 py-1 ${
                                                        o.benar
                                                            ? 'bg-success-subtle text-success-emphasis fw-semibold'
                                                            : 'text-body'
                                                    }`}
                                                >
                                                    <span class={`badge ${o.benar ? 'bg-success' : 'bg-secondary-subtle text-secondary-emphasis'}`} style="min-width: 24px;">
                                                        {huruf(i)}
                                                    </span>
                                                    <span class="flex-grow-1">{o.teks}</span>
                                                    {#if o.benar}
                                                        <i class="bi bi-check-circle-fill"></i>
                                                    {/if}
                                                </div>
                                            {/each}
                                        </div>
                                    {:else if b.kunci_isian?.length}
                                        <div class="small text-muted mt-2">
                                            <i class="bi bi-key me-1"></i>
                                            Kunci: {Array.isArray(b.kunci_isian) ? b.kunci_isian.join(', ') : (b.kunci_isian ?? '-')}
                                            {#if b.isian_case_sensitive}
                                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Case sensitive</span>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>
                                <div class="d-inline-flex gap-1 flex-shrink-0">
                                    <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(b)} title="Edit soal">
                                        <i class="bi bi-pencil"></i>
                                    </Button>
                                    <Button size="sm" color="outline-danger" onclick={() => hapus(b)} title="Hapus soal">
                                        <i class="bi bi-trash"></i>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    {/each}
                    <Pagination meta={bank} onPageChange={goToPage} />
                {/if}
            </CardBody>
        </Card>
    {/if}

    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>
            {editId ? 'Edit Soal Bank' : 'Tambah Soal Bank'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="b-matpel">Mata Pelajaran</label>
                    <Select
                        id="b-matpel"
                        items={matpelOptions}
                        value={form.matpel_id}
                        placeholder="Pilih matpel"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => (form.matpel_id = extractId(v))}
                    />
                    {#if form.errors.matpel_id}<div class="text-danger small">{form.errors.matpel_id}</div>{/if}
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="b-tipe">Tipe</label>
                    <select id="b-tipe" class="form-select" value={form.tipe} onchange={(e) => gantiTipe((e.currentTarget as HTMLSelectElement).value as TipeSoal)}>
                        {#each tipeOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="b-poin">Poin</label>
                    <input id="b-poin" type="number" min="1" class="form-control" bind:value={form.poin} />
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="b-topik">Topik <span class="text-muted fw-normal">(opsional)</span></label>
                    <input id="b-topik" class="form-control" bind:value={form.topik} placeholder="mis. Aljabar" />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="b-kesulitan">Kesulitan</label>
                    <select id="b-kesulitan" class="form-select" bind:value={form.kesulitan}>
                        {#each kesulitanOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="b-pertanyaan">Pertanyaan</label>
                    {#key editId ?? 'baru'}
                        <RichTextEditor
                            id="b-pertanyaan"
                            value={form.pertanyaan}
                            placeholder="Tulis pertanyaan di sini… (tombol fx untuk rumus matematika)"
                            invalid={!!form.errors.pertanyaan}
                            onchange={(html) => (form.pertanyaan = html)}
                        />
                    {/key}
                    {#if form.errors.pertanyaan}<div class="text-danger small">{form.errors.pertanyaan}</div>{/if}
                </div>

                {#if butuhOpsi}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">
                                Opsi Jawaban
                                <span class="text-muted fw-normal small">
                                    {form.tipe === 'multi' ? '(centang semua jawaban benar)' : '(pilih satu jawaban benar)'}
                                </span>
                            </span>
                            {#if form.tipe !== 'benar_salah'}
                                <Button size="sm" color="outline-primary" onclick={tambahOpsi}>
                                    <i class="bi bi-plus"></i> Tambah Opsi
                                </Button>
                            {/if}
                        </div>
                        {#each form.opsi as _, i (i)}
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light fw-bold text-secondary" style="min-width: 48px; text-align: center;">
                                    {huruf(i)}
                                </span>
                                <span class="input-group-text">
                                    {#if form.tipe === 'multi'}
                                        <input type="checkbox" checked={form.opsi[i].benar} onchange={(e) => (form.opsi[i].benar = (e.currentTarget as HTMLInputElement).checked)} class="form-check-input mt-0" aria-label="Jawaban benar" />
                                    {:else}
                                        <input type="radio" name="bank-opsi-benar" checked={form.opsi[i].benar} onchange={() => setBenarTunggal(i)} class="form-check-input mt-0" aria-label="Jawaban benar" />
                                    {/if}
                                </span>
                                <input class="form-control" placeholder={`Isi teks opsi ${huruf(i)}`} bind:value={form.opsi[i].teks} readonly={form.tipe === 'benar_salah'} />
                                {#if form.tipe !== 'benar_salah' && form.opsi.length > 2}
                                    <Button color="outline-danger" onclick={() => hapusOpsi(i)} class="ms-1" title="Hapus opsi">
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        {#if form.errors.opsi}<div class="text-danger small">{form.errors.opsi}</div>{/if}
                    </div>
                {:else if form.tipe === 'isian'}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Kunci Jawaban</span>
                            <Button size="sm" color="outline-primary" onclick={tambahKunci}>
                                <i class="bi bi-plus"></i> Kunci
                            </Button>
                        </div>
                        {#each form.kunci_isian as _, i (i)}
                            <div class="input-group mb-2">
                                <input class="form-control" bind:value={form.kunci_isian[i]} placeholder={`Kunci ${i + 1}`} />
                                {#if form.kunci_isian.length > 1}
                                    <Button color="outline-danger" onclick={() => hapusKunci(i)} title="Hapus kunci">
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="b-case" bind:checked={form.isian_case_sensitive} />
                            <label class="form-check-label" for="b-case">Perhatikan huruf besar/kecil</label>
                        </div>
                        {#if form.errors.kunci_isian}<div class="text-danger small">{form.errors.kunci_isian}</div>{/if}
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
</div>
