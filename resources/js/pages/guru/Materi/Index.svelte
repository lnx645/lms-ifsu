<script lang="ts">
    import { router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Alert,
        Badge,
        Button,
        Card,
        CardBody,
        Modal,
        ModalBody,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import MateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import RichTextEditor from '@/components/RichTextEditor.svelte';
    import Select from '@/components/Select.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import { extractId } from '@/lib/utils';
    import { formatBytes } from '@/lib/materi';
    import type { PaginationMeta, PenugasanOption } from '@/types/models';

    type MateriItem = {
        id: number;
        judul: string;
        deskripsi: string | null;
        konten: string | null;
        file_name: string | null;
        file_size: number;
        kelas: string | null;
        matpel: string | null;
        dibuat_pada: string;
    };

    type KatalogItem = {
        id: number;
        judul: string;
        deskripsi: string | null;
        file_name: string | null;
        kelas: string | null;
        matpel: string | null;
        tahun_ajaran: string | null;
        guru: string | null;
    };

    type KatalogMeta = {
        data: KatalogItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
    };

    type KatalogFilters = {
        tahunAjaran: { value: number; label: string }[];
        kelas: KatalogKelasOption[];
        matpel: KatalogMatpelOption[];
    };

    type EditMateri = {
        id: number;
        guru_kelas_id: number | null;
        judul: string;
        deskripsi: string | null;
        konten: string | null;
        file_name: string | null;
        file_size: number;
    };

    type KatalogKelasOption = {
        value: number;
        label: string;
        taIds: number[];
        matpelIds: number[];
    };

    type KatalogMatpelOption = {
        value: number;
        label: string;
        taIds: number[];
        kelasIds: number[];
    };

    const ALLOWED_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx',
        'xls',
        'xlsx',
        'jpg',
        'jpeg',
        'png',
        'zip',
        'mp4',
        'mp3',
        'txt',
    ];
    const MAX_FILE_SIZE = 20 * 1024 * 1024;

    let {
        materis,
        penugasan = [],
        katalog = null,
        guru_kelas_id = null,
        katalogFilters = { tahunAjaran: [], kelas: [], matpel: [] },
        filters = { guru_kelas_id: null, q: '' },
        editMateri = null,
    }: {
        guru_kelas_id?: string | null;
        materis: PaginationMeta & { data: MateriItem[] };
        penugasan: PenugasanOption[];
        katalog: KatalogMeta | null;
        katalogFilters: KatalogFilters;
        filters: { guru_kelas_id: number | null; q: string };
        editMateri: EditMateri | null;
    } = $props();

    const form = useForm({
        guru_kelas_id: null as number | null,
        judul: '',
        deskripsi: '',
        konten: '',
        file: null as File | null,
    });

    const editForm = useForm({
        guru_kelas_id: null as number | null,
        judul: '',
        deskripsi: '',
        konten: '',
        file: null as File | null,
    });

    // svelte-ignore state_referenced_locally
    let filterGuruKelasId: number | null = $state(
        filters.guru_kelas_id,
    );
    // svelte-ignore state_referenced_locally
    let searchInput: string = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let fileError = $state('');

    let editOpen = $state(false);
    let editFileError = $state('');
    let editFileHapus = $state(false);

    $effect(() => {
        if (!editMateri) {
            return;
        }

        editForm.guru_kelas_id = editMateri.guru_kelas_id;
        editForm.judul = editMateri.judul;
        editForm.deskripsi = editMateri.deskripsi ?? '';
        editForm.konten = editMateri.konten ?? '';
        editForm.file = null;
        editFileError = '';
        editFileHapus = false;
    });

    let katalogOpen = $state(false);
    let katalogTa: number | '' = $state('');
    let katalogKelas: number | '' = $state('');
    let katalogMatpel: number | '' = $state('');
    let katalogQ = $state('');
    let katalogTimer: ReturnType<typeof setTimeout> | undefined;
    let katalogLoading = $state(false);

    const katalogUrl = MateriController.katalog().url;

    const katalogKelasList = $derived(
        katalogFilters.kelas.filter(
            (k) =>
                (!katalogTa || k.taIds.includes(Number(katalogTa))) &&
                (!katalogMatpel ||
                    k.matpelIds.includes(Number(katalogMatpel))),
        ),
    );

    const katalogMatpelList = $derived(
        katalogFilters.matpel.filter(
            (m) =>
                (!katalogTa || m.taIds.includes(Number(katalogTa))) &&
                (!katalogKelas ||
                    m.kelasIds.includes(Number(katalogKelas))),
        ),
    );

    function muatKatalog(page = 1) {
        katalogLoading = true;
        router.get(
            katalogUrl,
            {
                tahun_ajaran_id: extractId(katalogTa),
                kelas_id: extractId(katalogKelas),
                matpel_id: extractId(katalogMatpel),
                q: katalogQ.trim() || undefined,
                page: page > 1 ? page : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['katalog'],
                onSuccess: () => {
                    katalogLoading = false;
                },
            },
        );
    }

    function onKatalogSearch() {
        clearTimeout(katalogTimer);
        katalogTimer = setTimeout(() => muatKatalog(), 400);
    }

    function salinKatalog(item: KatalogItem) {
        if (!form.guru_kelas_id) {
            return;
        }

        confirm
            .show({
                title: 'Salin Materi',
                message: `Salin "${item.judul}" ke ${penugasan.find((p) => p.value === form.guru_kelas_id)?.label ?? 'penugasan terpilih'}?`,
                confirmText: 'Ya, Salin',
                color: 'primary',
            })
            .then((ok) => {
                if (!ok) {
                    return;
                }

                router.post(
                    MateriController.salin().url,
                    {
                        guru_kelas_id: form.guru_kelas_id,
                        materi_id: item.id,
                    },
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            katalogOpen = false;
                        },
                    },
                );
            });
    }

    function stripHtml(html: string | null): string {
        if (!html) {
            return '';
        }

        return html
            .replace(/&nbsp;/gi, ' ')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#0*39;/g, "'")
            .replace(/&amp;/g, '&')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function fileIcon(fileName: string): {
        icon: string;
        color: string;
    } {
        const ext = fileName.split('.').pop()?.toLowerCase();

        switch (ext) {
            case 'pdf':
                return {
                    icon: 'bi-file-earmark-pdf',
                    color: 'text-danger',
                };
            case 'doc':
            case 'docx':
                return {
                    icon: 'bi-file-earmark-word',
                    color: 'text-primary',
                };
            case 'xls':
            case 'xlsx':
                return {
                    icon: 'bi-file-earmark-excel',
                    color: 'text-success',
                };
            case 'ppt':
            case 'pptx':
                return {
                    icon: 'bi-file-earmark-ppt',
                    color: 'text-warning',
                };
            case 'zip':
                return {
                    icon: 'bi-file-earmark-zip',
                    color: 'text-secondary',
                };
            case 'mp4':
                return {
                    icon: 'bi-file-earmark-play',
                    color: 'text-info',
                };
            default:
                return {
                    icon: 'bi-file-earmark-text',
                    color: 'text-muted',
                };
        }
    }

    function setFile(file: File | null) {
        fileError = '';

        if (!file) {
            form.file = null;

            return;
        }

        const ext = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            fileError = 'Format berkas tidak didukung.';
            form.file = null;

            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            fileError = 'Ukuran berkas maksimal 20 MB.';
            form.file = null;

            return;
        }

        form.file = file;
    }

    function setEditFile(file: File | null) {
        editFileError = '';

        if (!file) {
            editForm.file = null;

            return;
        }

        const ext = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            editFileError = 'Format berkas tidak didukung.';
            editForm.file = null;

            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            editFileError = 'Ukuran berkas maksimal 20 MB.';
            editForm.file = null;

            return;
        }

        editForm.file = file;
    }

    function bukaEdit(item: MateriItem) {
        router.get(
            MateriController.edit({ materi: item.id }).url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['editMateri'],
                onSuccess: () => {
                    editOpen = true;
                },
            },
        );
    }

    function tutupEdit() {
        editOpen = false;
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['editMateri'],
                onSuccess: () => {
                    editForm.clearErrors();
                },
            },
        );
    }

    function simpanEdit() {
        if (!editMateri) {
            return;
        }

        editForm.put(
            MateriController.update({ materi: editMateri.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    editOpen = false;
                },
            },
        );
    }

    function unggah() {
        form.post(MateriController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('judul', 'deskripsi', 'konten', 'file');
                fileError = '';
            },
        });
    }

    async function hapus(item: MateriItem) {
        const ok = await confirm.show({
            title: 'Hapus Materi?',
            message: `Materi "${item.judul}" beserta berkasnya akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });

        if (!ok) {
            return;
        }

        router.delete(
            MateriController.destroy({ materi: item.id }).url,
            {
                preserveScroll: true,
            },
        );
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                guru_kelas_id: filterGuruKelasId ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['materis'],
            },
        );
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
                guru_kelas_id: filterGuruKelasId ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['materis'],
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Materi"
        subtitle="Unggah materi pembelajaran untuk kelas yang kamu ajar pada tahun ajaran aktif."
    />

    <div class="d-flex gap-2 flex-wrap mb-3">
        <span class="stat-chip">
            <i class="bi bi-files me-1 text-primary"></i>
            {materis?.total ?? 0} Materi
        </span>
        <span class="stat-chip">
            <i class="bi bi-journal-bookmark me-1 text-primary"></i>
            {penugasan.length} Penugasan Aktif
        </span>
    </div>

    {#if penugasan.length}
        <Card class="border rounded-1 shadow-sm mb-3">
            <CardBody class="p-3 p-md-4">
                <h2 class="h6 fw-semibold mb-3">
                    <i class="bi bi-cloud-arrow-up me-1 text-primary"
                    ></i>
                    Unggah Materi Baru
                    <Button
                        size="sm"
                        color="outline-secondary"
                        class="ms-2"
                        onclick={() => {
                            if (!form.guru_kelas_id) {
                                confirm.show({
                                    title: 'Pilih Penugasan Dulu',
                                    message:
                                        'Pilih Kelas & Mata Pelajaran di atas sebelum menyalin materi.',
                                    confirmText: 'Mengerti',
                                    color: 'warning',
                                });

                                return;
                            }

                            katalogOpen = true;
                            muatKatalog();
                        }}
                    >
                        <i class="bi bi-copy me-1"></i>Salin Materi
                    </Button>
                </h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="materi-kelas" class="form-label"
                            >Kelas &amp; Mata Pelajaran</label
                        >
                        <Select
                            id="materi-kelas"
                            items={penugasan}
                            value={form.guru_kelas_id}
                            placeholder="Pilih kelas &amp; mata pelajaran…"
                            getOptionValue={(item) => item.value}
                            onchange={(v) =>
                                (form.guru_kelas_id = extractId(v))}
                            hasError={Boolean(
                                form.errors.guru_kelas_id,
                            )}
                        />
                        {#if form.errors.guru_kelas_id}
                            <div class="invalid-feedback d-block">
                                {form.errors.guru_kelas_id}
                            </div>
                        {/if}
                    </div>
                    <div class="col-md-6">
                        <label for="materi-judul" class="form-label"
                            >Judul Materi</label
                        >
                        <input
                            id="materi-judul"
                            type="text"
                            class="form-control {form.errors.judul
                                ? 'is-invalid'
                                : ''}"
                            placeholder="Contoh: Bab 1 – Pengenalan Pemrograman"
                            value={form.judul}
                            oninput={(e) =>
                                (form.judul = (
                                    e.currentTarget as HTMLInputElement
                                ).value)}
                        />
                        {#if form.errors.judul}
                            <div class="invalid-feedback">
                                {form.errors.judul}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label
                            for="materi-deskripsi"
                            class="form-label"
                            >Deskripsi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <textarea
                            id="materi-deskripsi"
                            rows="3"
                            class="form-control {form.errors.deskripsi
                                ? 'is-invalid'
                                : ''}"
                            placeholder="Keterangan singkat materi"
                            value={form.deskripsi}
                            oninput={(e) =>
                                (form.deskripsi = (
                                    e.currentTarget as HTMLTextAreaElement
                                ).value)}
                        ></textarea>
                        {#if form.errors.deskripsi}
                            <div class="invalid-feedback d-block">
                                {form.errors.deskripsi}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label for="materi-konten" class="form-label"
                            >Isi Materi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <RichTextEditor
                            id="materi-konten"
                            value={form.konten}
                            placeholder="Tulis isi materi di sini, atau lampirkan berkas di bawah…"
                            invalid={Boolean(form.errors.konten)}
                            onchange={(html) => (form.konten = html)}
                        />
                        {#if form.errors.konten}
                            <div class="invalid-feedback d-block">
                                {form.errors.konten}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label for="materi-file" class="form-label"
                            >Berkas Materi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <input
                            id="materi-file"
                            type="file"
                            class="form-control {form.errors.file ||
                            fileError
                                ? 'is-invalid'
                                : ''}"
                            accept={ALLOWED_EXTENSIONS.map(
                                (ext) => `.${ext}`,
                            ).join(',')}
                            onchange={(e) =>
                                setFile(
                                    (
                                        e.currentTarget as HTMLInputElement
                                    ).files?.[0] ?? null,
                                )}
                        />
                        {#if form.file}
                            <div
                                class="form-text d-flex align-items-center gap-2"
                            >
                                <i
                                    class={`bi ${fileIcon(form.file.name).icon} ${fileIcon(form.file.name).color}`}
                                ></i>
                                <span class="text-truncate"
                                    >{form.file.name} ({formatBytes(
                                        form.file.size,
                                    )})</span
                                >
                            </div>
                        {:else}
                            <div class="form-text text-xs">
                                PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX,
                                JPG, PNG, ZIP, MP4, MP3, TXT — maks.
                                20 MB
                            </div>
                        {/if}
                        {#if fileError}
                            <div class="invalid-feedback d-block">
                                {fileError}
                            </div>
                        {:else if form.errors.file}
                            <div class="invalid-feedback d-block">
                                {form.errors.file}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <Button
                            color="primary"
                            onclick={unggah}
                            disabled={form.processing}
                        >
                            {#if form.processing}
                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                            {:else}
                                <i class="bi bi-cloud-arrow-up me-1"
                                ></i>
                            {/if}
                            Unggah Materi
                        </Button>
                    </div>
                </div>
            </CardBody>
        </Card>
    {:else}
        <Alert color="info" class="border rounded-1 shadow-sm mb-3">
            <i class="bi bi-info-circle me-2"></i>
            Kamu belum memiliki penugasan kelas aktif pada tahun ajaran
            ini. Hubungi admin untuk menambahkan penugasan, lalu unggah
            materi di sini.
        </Alert>
    {/if}

    <Card class="border rounded-1 shadow-sm mb-3">
        <CardBody class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-penugasan" class="form-label"
                        >Kelas &amp; Mata Pelajaran</label
                    >
                    <Select
                        id="filter-penugasan"
                        items={penugasan}
                        value={filterGuruKelasId}
                        placeholder="Semua penugasan"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterGuruKelasId = extractId(v);
                            reload();
                        }}
                        onclear={() => {
                            filterGuruKelasId = null;
                            reload();
                        }}
                    />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-q" class="form-label"
                        >Cari</label
                    >
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="bi bi-search"></i></span
                        >
                        <input
                            id="filter-q"
                            type="search"
                            class="form-control"
                            placeholder="Cari judul materi…"
                            value={searchInput}
                            oninput={(e) => {
                                searchInput = (
                                    e.currentTarget as HTMLInputElement
                                ).value;
                                onSearchInput();
                            }}
                        />
                    </div>
                </div>
                <div
                    class="col-12 col-lg-4 text-lg-end text-muted small"
                >
                    {#if materis?.total > 0}
                        Menampilkan {materis.from ?? 0}–{materis.to ??
                            0} dari {materis.total} materi
                    {:else}
                        Tidak ada materi
                    {/if}
                </div>
            </div>
        </CardBody>
    </Card>

    <Card class="border rounded-1 shadow-sm">
        {#if materis?.data.length}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Judul</th>
                            <th scope="col">Kelas &amp; Matpel</th>
                            <th scope="col">Berkas</th>
                            <th scope="col">Diunggah</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each materis.data as item (item.id)}
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {item.judul}
                                    </div>
                                    {#if item.deskripsi || item.konten}
                                        <div
                                            class="text-muted small materi-deskripsi-snippet"
                                            title={stripHtml(
                                                item.deskripsi ??
                                                    item.konten,
                                            )}
                                        >
                                            {stripHtml(
                                                item.deskripsi ??
                                                    item.konten,
                                            )}
                                        </div>
                                    {/if}
                                </td>
                                <td>
                                    <Badge
                                        color="primary"
                                        class="me-1"
                                        >{item.kelas ?? '—'}</Badge
                                    >
                                    <Badge color="secondary"
                                        >{item.matpel ?? '—'}</Badge
                                    >
                                </td>
                                <td>
                                    {#if item.file_name}
                                        <i
                                            class={`bi ${fileIcon(item.file_name).icon} me-1 ${fileIcon(item.file_name).color}`}
                                        ></i>
                                        <span
                                            class="text-muted small"
                                        >
                                            {item.file_name} ({formatBytes(
                                                item.file_size,
                                            )})
                                        </span>
                                    {:else}
                                        <span class="text-muted"
                                            >—</span
                                        >
                                    {/if}
                                </td>
                                <td class="text-muted small"
                                    >{item.dibuat_pada}</td
                                >
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <Button
                                            size="sm"
                                            color="outline-secondary"
                                            onclick={() =>
                                                bukaEdit(item)}
                                            title="Edit materi"
                                        >
                                            <i class="bi bi-pencil"
                                            ></i>
                                        </Button>
                                        {#if item.file_name}
                                            <a
                                                href={MateriController.unduh(
                                                    {
                                                        materi: item.id,
                                                    },
                                                ).url}
                                                class="btn btn-sm btn-outline-primary"
                                                title="Unduh berkas"
                                            >
                                                <i
                                                    class="bi bi-download"
                                                ></i>
                                            </a>
                                        {/if}
                                        <Button
                                            size="sm"
                                            color="outline-danger"
                                            onclick={() =>
                                                hapus(item)}
                                            title="Hapus materi"
                                        >
                                            <i class="bi bi-trash"
                                            ></i>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
            <Pagination meta={materis} onPageChange={goToPage} />
        {:else}
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i
                        class="bi bi-journal-plus"
                        style="font-size: 3rem"
                    ></i>
                    <p class="mt-3 mb-0">
                        {#if filterGuruKelasId || searchInput.trim()}
                            Tidak ada materi yang cocok dengan filter
                            yang dipilih. Coba ubah atau bersihkan
                            filter.
                        {:else}
                            Belum ada materi. Unggah materi pertama
                            kamu melalui formulir di atas.
                        {/if}
                    </p>
                </div>
            </CardBody>
        {/if}
    </Card>

    <Modal
        isOpen={katalogOpen}
        toggle={() => (katalogOpen = false)}
        scrollable
        size="lg"
    >
        <ModalHeader toggle={() => (katalogOpen = false)}>
            <i class="bi bi-copy me-2 text-primary"></i>Salin Materi
        </ModalHeader>
        <ModalBody>
            <p class="text-muted small mb-3">
                Pilih materi dari seluruh materi yang pernah dibuat
                (semua guru &amp; tahun ajaran) untuk disalin ke:
                <span class="fw-semibold text-primary">
                    {penugasan.find(
                        (p) => p.value === form.guru_kelas_id,
                    )?.label ?? '—'}
                </span>
            </p>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <label
                        class="form-label small mb-1"
                        for="katalog-ta">Tahun Ajaran</label
                    >
                    <Select
                        id="katalog-ta"
                        items={katalogFilters.tahunAjaran}
                        value={katalogTa}
                        placeholder="Semua"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            katalogTa = extractId(v) ?? '';
                            muatKatalog();
                        }}
                    />
                </div>
                <div class="col-6 col-md-3">
                    <label
                        class="form-label small mb-1"
                        for="katalog-kelas">Kelas</label
                    >
                    <Select
                        id="katalog-kelas"
                        items={katalogKelasList}
                        value={katalogKelas}
                        placeholder="Semua"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            katalogKelas = extractId(v) ?? '';
                            muatKatalog();
                        }}
                    />
                </div>
                <div class="col-6 col-md-3">
                    <label
                        class="form-label small mb-1"
                        for="katalog-matpel">Mata Pelajaran</label
                    >
                    <Select
                        id="katalog-matpel"
                        items={katalogMatpelList}
                        value={katalogMatpel}
                        placeholder="Semua"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            katalogMatpel = extractId(v) ?? '';
                            muatKatalog();
                        }}
                    />
                </div>
                <div class="col-6 col-md-3">
                    <label
                        class="form-label small mb-1"
                        for="katalog-q">Cari Judul</label
                    >
                    <input
                        id="katalog-q"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Ketik judul…"
                        value={katalogQ}
                        oninput={(e) => {
                            katalogQ = (
                                e.currentTarget as HTMLInputElement
                            ).value;
                            onKatalogSearch();
                        }}
                    />
                </div>
            </div>

            {#if katalogLoading}
                <div class="text-center py-4 text-secondary">
                    <div
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                    ></div>
                    Memuat materi…
                </div>
            {:else if katalog?.data.length}
                <div class="list-group katalog-list">
                    {#each katalog.data as item (item.id)}
                        <div
                            class="list-group-item d-flex align-items-center gap-2"
                        >
                            <div class="flex-grow-1 min-w-0">
                                <div
                                    class="fw-semibold text-truncate"
                                >
                                    {item.judul}
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-person me-1"
                                    ></i>{item.guru ?? 'Guru'}
                                    <span class="mx-1">·</span>
                                    <Badge
                                        color="primary"
                                        class="me-1"
                                        >{item.kelas ?? '—'}</Badge
                                    >
                                    <Badge
                                        color="secondary"
                                        class="me-1"
                                        >{item.matpel ?? '—'}</Badge
                                    >
                                    {#if item.file_name}
                                        <span class="mx-1">·</span>
                                        <i
                                            class={`bi ${fileIcon(item.file_name).icon} ${fileIcon(item.file_name).color}`}
                                        ></i>
                                    {/if}
                                </div>
                            </div>
                            <span
                                class="badge text-bg-light border text-muted small flex-shrink-0"
                            >
                                {item.tahun_ajaran ?? '—'}
                            </span>
                            <Button
                                size="sm"
                                color="primary"
                                onclick={() => salinKatalog(item)}
                                title="Salin ke penugasan terpilih"
                            >
                                <i class="bi bi-copy me-1"></i>Salin
                            </Button>
                        </div>
                    {/each}
                </div>
                {#if katalog.last_page > 1}
                    <div class="mt-3 d-flex align-items-center gap-2">
                        <Button
                            size="sm"
                            color="outline-secondary"
                            disabled={katalog.current_page <= 1}
                            onclick={() =>
                                muatKatalog(katalog.current_page - 1)}
                        >
                            <i class="bi bi-chevron-left"></i>
                        </Button>
                        <span class="small text-muted">
                            Hal {katalog.current_page} dari {katalog.last_page}
                            ({katalog.total} materi)
                        </span>
                        <Button
                            size="sm"
                            color="outline-secondary"
                            disabled={katalog.current_page >=
                                katalog.last_page}
                            onclick={() =>
                                muatKatalog(katalog.current_page + 1)}
                        >
                            <i class="bi bi-chevron-right"></i>
                        </Button>
                    </div>
                {/if}
            {:else}
                <div class="text-center py-4 text-secondary">
                    <i class="bi bi-inbox" style="font-size: 2rem"
                    ></i>
                    <p class="mt-2 mb-0">
                        Tidak ada materi yang cocok dengan filter.
                    </p>
                </div>
            {/if}
        </ModalBody>
    </Modal>

    <Modal isOpen={editOpen} toggle={tutupEdit} scrollable size="lg">
        <ModalHeader toggle={tutupEdit}>
            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit
            Materi
        </ModalHeader>
        <ModalBody>
            {#if editMateri}
                <div class="row g-3">
                    <div class="col-md-6">
                        <label
                            for="edit-materi-kelas"
                            class="form-label"
                            >Kelas &amp; Mata Pelajaran</label
                        >
                        <Select
                            id="edit-materi-kelas"
                            items={penugasan}
                            value={editForm.guru_kelas_id}
                            placeholder="Pilih kelas &amp; mata pelajaran…"
                            getOptionValue={(item) => item.value}
                            onchange={(v) =>
                                (editForm.guru_kelas_id =
                                    extractId(v))}
                            hasError={Boolean(
                                editForm.errors.guru_kelas_id,
                            )}
                        />
                        {#if editForm.errors.guru_kelas_id}
                            <div class="invalid-feedback d-block">
                                {editForm.errors.guru_kelas_id}
                            </div>
                        {/if}
                    </div>
                    <div class="col-md-6">
                        <label
                            for="edit-materi-judul"
                            class="form-label">Judul Materi</label
                        >
                        <input
                            id="edit-materi-judul"
                            type="text"
                            class="form-control {editForm.errors.judul
                                ? 'is-invalid'
                                : ''}"
                            placeholder="Contoh: Bab 1 – Pengenalan Pemrograman"
                            value={editForm.judul}
                            oninput={(e) =>
                                (editForm.judul = (
                                    e.currentTarget as HTMLInputElement
                                ).value)}
                        />
                        {#if editForm.errors.judul}
                            <div class="invalid-feedback">
                                {editForm.errors.judul}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label
                            for="edit-materi-deskripsi"
                            class="form-label"
                            >Deskripsi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <textarea
                            id="edit-materi-deskripsi"
                            rows="3"
                            class="form-control {editForm.errors
                                .deskripsi
                                ? 'is-invalid'
                                : ''}"
                            placeholder="Keterangan singkat materi"
                            value={editForm.deskripsi}
                            oninput={(e) =>
                                (editForm.deskripsi = (
                                    e.currentTarget as HTMLTextAreaElement
                                ).value)}
                        ></textarea>
                        {#if editForm.errors.deskripsi}
                            <div class="invalid-feedback d-block">
                                {editForm.errors.deskripsi}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label
                            for="edit-materi-konten"
                            class="form-label"
                            >Isi Materi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <RichTextEditor
                            id="edit-materi-konten"
                            value={editForm.konten}
                            placeholder="Tulis isi materi di sini, atau lampirkan berkas di bawah…"
                            invalid={Boolean(editForm.errors.konten)}
                            onchange={(html) =>
                                (editForm.konten = html)}
                        />
                        {#if editForm.errors.konten}
                            <div class="invalid-feedback d-block">
                                {editForm.errors.konten}
                            </div>
                        {/if}
                    </div>
                    <div class="col-12">
                        <label
                            for="edit-materi-file"
                            class="form-label"
                            >Berkas Materi <span
                                class="text-muted fw-normal"
                                >(opsional)</span
                            ></label
                        >
                        <input
                            id="edit-materi-file"
                            type="file"
                            class="form-control {editForm.errors
                                .file || editFileError
                                ? 'is-invalid'
                                : ''}"
                            accept={ALLOWED_EXTENSIONS.map(
                                (ext) => `.${ext}`,
                            ).join(',')}
                            onchange={(e) =>
                                setEditFile(
                                    (
                                        e.currentTarget as HTMLInputElement
                                    ).files?.[0] ?? null,
                                )}
                        />
                        {#if editForm.file}
                            <div
                                class="form-text d-flex align-items-center gap-2"
                            >
                                <i
                                    class={`bi ${fileIcon(editForm.file.name).icon} ${fileIcon(editForm.file.name).color}`}
                                ></i>
                                <span class="text-truncate"
                                    >Berkas baru: {editForm.file.name} ({formatBytes(
                                        editForm.file.size,
                                    )})</span
                                >
                            </div>
                        {:else if editMateri.file_name && !editFileHapus}
                            <div
                                class="form-text d-flex align-items-center gap-2"
                            >
                                <i
                                    class={`bi ${fileIcon(editMateri.file_name).icon} ${fileIcon(editMateri.file_name).color}`}
                                ></i>
                                <span class="text-truncate">
                                    Berkas saat ini: {editMateri.file_name}
                                    ({formatBytes(
                                        editMateri.file_size,
                                    )})
                                </span>
                            </div>
                            <div class="form-text">
                                Kosongkan kolom ini untuk tetap
                                memakai berkas yang sama.
                            </div>
                        {:else}
                            <div class="form-text tex-xs">
                                PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX,
                                JPG, PNG, ZIP, MP4, MP3, TXT — maks.
                                20 MB
                            </div>
                        {/if}
                        {#if editFileError}
                            <div class="invalid-feedback d-block">
                                {editFileError}
                            </div>
                        {:else if editForm.errors.file}
                            <div class="invalid-feedback d-block">
                                {editForm.errors.file}
                            </div>
                        {/if}
                    </div>
                    <div
                        class="col-12 d-flex justify-content-end gap-2"
                    >
                        <Button
                            color="outline-secondary"
                            onclick={tutupEdit}
                            disabled={editForm.processing}
                        >
                            Batal
                        </Button>
                        <Button
                            color="primary"
                            onclick={simpanEdit}
                            disabled={editForm.processing}
                        >
                            {#if editForm.processing}
                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                            {:else}
                                <i class="bi bi-check-lg me-1"></i>
                            {/if}
                            Simpan Perubahan
                        </Button>
                    </div>
                </div>
            {/if}
        </ModalBody>
    </Modal>
</div>

<style>
    .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        background: var(--bs-primary-bg-subtle);
        font-size: var(--bs-font-size-sm);
        font-weight: 600;
        color: var(--bs-primary-700, var(--bs-body-color));
    }

    .materi-deskripsi-snippet {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        max-width: 340px;
    }
</style>
