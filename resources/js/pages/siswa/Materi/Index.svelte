<script lang="ts">
    import { inertia, router, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Card,
        CardBody,
    } from '@sveltestrap/sveltestrap';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import { extractId } from '@/lib/utils';
    import { formatBytes } from '@/lib/materi';
    import type { PaginationMeta } from '@/types/models';

    type MateriItem = {
        id: number;
        judul: string;
        deskripsi: string | null;
        file_name: string | null;
        file_size: number;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        dibuat_pada: string;
    };

    let {
        materis,
        kelas = null,
        matpelList = [],
        filters = { matpel: null, q: '' },
    }: {
        materis: PaginationMeta & { data: MateriItem[] };
        kelas: string | null;
        matpelList: { value: number; label: string }[];
        filters: { matpel: number | null; q: string };
    } = $props();

    let filterMatpel = $state(filters.matpel);
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function stripHtml(html: string | null): string {
        if (!html) return '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent?.trim() ?? '';
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                matpel: filterMatpel ?? undefined,
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
                matpel: filterMatpel ?? undefined,
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

    // Ikon & variasi warna badge berkas berdasar ekstensi file.
    function fileExt(fileName: string | null): string {
        return (fileName ?? '').split('.').pop()?.toLowerCase() ?? '';
    }

    function materiKind(item: MateriItem): string {
        const ext = fileExt(item.file_name);
        if (['pdf'].includes(ext)) return 'pdf';
        if (['doc', 'docx'].includes(ext)) return 'doc';
        if (['xls', 'xlsx'].includes(ext)) return 'xls';
        if (['ppt', 'pptx'].includes(ext)) return 'ppt';
        if (['zip', 'rar', '7z'].includes(ext)) return 'zip';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext))
            return 'img';
        if (['mp4', 'mkv', 'avi', 'mov'].includes(ext)) return 'video';
        if (['mp3', 'wav', 'ogg'].includes(ext)) return 'audio';
        if (['txt', 'md'].includes(ext)) return 'text';
        return 'file';
    }

    function materiIcon(item: MateriItem): string {
        const ext = fileExt(item.file_name);
        if (['zip', 'rar', '7z'].includes(ext)) return 'bi-file-earmark-zip';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext))
            return 'bi-file-earmark-image';
        if (['mp4', 'mkv', 'avi', 'mov'].includes(ext))
            return 'bi-file-earmark-play';
        if (['mp3', 'wav', 'ogg'].includes(ext))
            return 'bi-file-earmark-music';
        if (['txt', 'md'].includes(ext)) return 'bi-file-earmark-text';
        if (['pdf'].includes(ext)) return 'bi-file-earmark-pdf';
        if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word';
        if (['xls', 'xlsx'].includes(ext)) return 'bi-file-earmark-excel';
        if (['ppt', 'pptx'].includes(ext)) return 'bi-file-earmark-ppt';
        return 'bi-file-earmark-richtext';
    }

</script>

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + judul -->
    <div class="materi-hero mb-4">
        <div class="materi-hero__inner">
            <img class="materi-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="materi-hero__text">
                <span class="materi-hero__eyebrow">Pembelajaran</span>
                <h1 class="materi-hero__title">Materi Pembelajaran</h1>
                <p class="materi-hero__subtitle">
                    {kelas
                        ? `Materi pembelajaran untuk kelas ${kelas} pada tahun ajaran aktif.`
                        : 'Materi pembelajaran yang dibagikan guru kepadamu.'}
                </p>
            </div>
        </div>
    </div>

    <Card class="border rounded-1 shadow-sm mb-3">
        <CardBody class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-matpel" class="form-label">Mata Pelajaran</label>
                    <Select
                        id="filter-matpel"
                        items={matpelList}
                        value={filterMatpel}
                        placeholder="Semua mata pelajaran"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterMatpel = extractId(v);
                            reload();
                        }}
                        onclear={() => {
                            filterMatpel = null;
                            reload();
                        }}
                    />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-q" class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input
                            id="filter-q"
                            type="search"
                            class="form-control"
                            placeholder="Cari judul materi…"
                            value={searchInput}
                            oninput={(e) => {
                                searchInput = (e.currentTarget as HTMLInputElement).value;
                                onSearchInput();
                            }}
                        />
                    </div>
                </div>
                <div class="col-12 col-lg-4 text-lg-end text-muted small">
                    {#if materis.total > 0}
                        Menampilkan {materis.from ?? 0}–{materis.to ?? 0} dari {materis.total} materi
                    {:else}
                        Tidak ada materi
                    {/if}
                </div>
            </div>
        </CardBody>
    </Card>

    {#if materis.data.length}
        <div class="row g-3">
            {#each materis.data as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-sm h-100 materi-card">
                        <CardBody class="d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div class="materi-file materi-file--{materiKind(item)}">
                                    <i class="bi {materiIcon(item)}"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex gap-1 flex-wrap mb-1">
                                        <Badge color="primary">{item.matpel ?? '—'}</Badge>
                                        <Badge color="secondary">{item.kelas ?? '—'}</Badge>
                                    </div>
                                    <h2 class="h6 fw-semibold mb-0 materi-judul">{item.judul}</h2>
                                </div>
                            </div>
                            {#if item.deskripsi}
                                <div class="text-muted small mb-2 flex-grow-1 rich-deskripsi">
                                    {stripHtml(item.deskripsi)}
                                </div>
                            {:else}
                                <div class="flex-grow-1"></div>
                            {/if}
                            <div class="materi-meta mb-3">
                                <span class="materi-meta__item">
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                                </span>
                                <span class="materi-meta__item">
                                    <i class="bi bi-calendar3 me-1"></i>{item.dibuat_pada}
                                </span>
                            </div>
                            {#if item.file_name}
                                <div class="materi-file-info mb-3">
                                    <i class="bi bi-paperclip me-1"></i>
                                    <span class="text-truncate">{item.file_name}</span>
                                    <span class="materi-file-info__size">{formatBytes(item.file_size)}</span>
                                </div>
                            {/if}
                            <div class="mt-auto d-flex gap-2 pt-2 border-top">
                                <a
                                    use:inertia
                                    href={MateriController.show({ materi: item.id }).url}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                                {#if item.file_name}
                                    <a
                                        href={MateriController.unduh({ materi: item.id }).url}
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="bi bi-download me-1"></i>Unduh
                                    </a>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
        <div class="mt-3">
            <Pagination meta={materis} onPageChange={goToPage} />
        </div>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="p-0">
                <EmptyState
                    icon="bi-book-half"
                    message={
                        filterMatpel || searchInput.trim()
                            ? 'Tidak ada materi yang cocok dengan filter yang dipilih. Coba ubah atau bersihkan filter.'
                            : kelas
                              ? 'Belum ada materi yang dibagikan untuk kelasmu.'
                              : 'Kamu belum terdaftar di kelas mana pun, sehingga belum ada materi yang bisa dilihat.'
                    }
                    variant="card"
                />
            </CardBody>
        </Card>
    {/if}
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .materi-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #3d5afe 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.15);
    }

    .materi-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .materi-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .materi-hero__text {
        min-width: 0;
    }

    .materi-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .materi-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
    }

    .materi-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
    }

    /* ---------- Kartu materi ---------- */
    .materi-card {
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .materi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }

    .materi-file {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 0.8rem;
        font-size: 1.35rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .materi-file--pdf,
    .materi-file--doc {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    .materi-file--xls {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .materi-file--ppt {
        background: linear-gradient(135deg, #ea580c, #c2410c);
    }
    .materi-file--img {
        background: linear-gradient(135deg, #d946ef, #a21caf);
    }
    .materi-file--video {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .materi-file--audio {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
    }
    .materi-file--zip {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    .materi-file--text,
    .materi-file--file {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .materi-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
    }

    .materi-meta__item {
        display: inline-flex;
        align-items: center;
    }

    .materi-file-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.5rem;
        font-size: 0.78rem;
        background: var(--bs-secondary-bg, var(--bs-body-bg));
        border: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
    }

    .materi-file-info .text-truncate {
        flex: 1 1 auto;
        min-width: 0;
    }

    .materi-file-info__size {
        font-weight: 600;
        flex-shrink: 0;
    }

    .rich-deskripsi {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .rich-deskripsi p {
        margin-bottom: 0.5rem;
    }

    .rich-deskripsi p:last-child {
        margin-bottom: 0;
    }

    .rich-deskripsi ul,
    .rich-deskripsi ol {
        margin-bottom: 0.5rem;
        padding-left: 1.25rem;
    }

    @media (max-width: 575.98px) {
        .materi-hero {
            padding: 1.1rem 1.1rem;
        }
        .materi-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .materi-hero__title {
            font-size: 1.25rem;
        }
    }
    /* Judul materi: wrap maksimal 2 baris, tidak overflow di HP */
    .materi-judul {
        overflow-wrap: break-word;
        word-break: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>