<script lang="ts">
    import { inertia, WhenVisible } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import DocxViewer from '@/components/DocxViewer.svelte';
    import PdfViewer from '@/components/PdfViewer.svelte';
    import PptxViewer from '@/components/PptxViewer.svelte';
    import XlsxViewer from '@/components/XlsxViewer.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import {
        fileStyleFor,
        formatBytes,
        formatFileType,
        viewerKindFor,
        type MateriDetail,
        type ViewerKind,
    } from '@/lib/materi';
    import { highlightKonten } from '@/lib/highlight-konten';
    import 'katex/dist/katex.min.css';
    import 'highlight.js/styles/github.css';

    let { materi, konten }: { materi: MateriDetail; konten?: string | null } = $props();

    let kontenHost: HTMLDivElement | undefined = $state();

    type DetailTab = 'materi' | 'deskripsi' | 'lampiran';

    const tabs = $derived(
        [
            materi.has_konten
                ? { key: 'materi' as DetailTab, label: 'Materi', icon: 'bi-journal-text' }
                : null,
            materi.deskripsi
                ? { key: 'deskripsi' as DetailTab, label: 'Deskripsi', icon: 'bi-chat-left-text' }
                : null,
            materi.file_name
                ? { key: 'lampiran' as DetailTab, label: 'Lampiran', icon: 'bi-paperclip' }
                : null,
        ].filter((t): t is NonNullable<typeof t> => t !== null),
    );

    let tab = $state<DetailTab>('materi');

    const activeTab = $derived(
        tabs.some((t) => t.key === tab) ? tab : (tabs[0]?.key ?? 'materi'),
    );

    const viewerKind: ViewerKind | null = viewerKindFor(
        materi.file_name,
        materi.mime_type,
    );
    const fileStyle = fileStyleFor(viewerKind);
    const unduhUrl = MateriController.unduh({
        materi: materi.id,
    }).url;
    const lihatUrl = MateriController.lihat({
        materi: materi.id,
    }).url;
    const kembaliUrl = MateriController.index().url;

    $effect(() => {
        void konten;
        if (!kontenHost) {
            return;
        }

        highlightKonten(kontenHost);
    });
</script>

<div class="container-fluid px-0">
    <div class="materi-header mb-3">
        <a use:inertia href={kembaliUrl} class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Daftar Materi
        </a>

        <div class="d-flex gap-2 flex-wrap mb-2">
            <Badge color="primary">{materi.matpel ?? 'Matpel'}</Badge>
            <Badge color="secondary">{materi.kelas ?? 'Kelas'}</Badge>
            {#if viewerKind}
                <Badge color="info">
                    <i class={`bi ${fileStyle?.icon} me-1`}></i>
                    {formatFileType(materi.mime_type, materi.file_name)}
                </Badge>
            {/if}
        </div>
        <h1 class="h4 fw-bold mb-1 text-break">{materi.judul}</h1>
        <div class="text-muted small">
            <i class="bi bi-person me-1"></i>{materi.guru ?? 'Guru'}
            <span class="mx-1">•</span>
            <i class="bi bi-calendar3 me-1"></i>{materi.dibuat_pada}
        </div>
    </div>

    {#if tabs.length > 0}
        <Card
            class="border rounded-top-0 shadow-none rounded-bottom-1 mt-0"
        >
            {#if tabs.length > 1}
                <ul class="nav detail-tabs">
                    {#each tabs as t (t.key)}
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link {activeTab === t.key ? 'active' : ''}"
                                onclick={() => (tab = t.key)}
                            >
                                <i class={`bi ${t.icon} me-1`}></i>{t.label}
                            </button>
                        </li>
                    {/each}
                </ul>
            {/if}

            <CardBody class="p-3 p-md-4">
                {#if activeTab === 'materi'}
                    <WhenVisible
                        data="konten"
                        params={{ only: ['konten'] }}
                        buffer={300}
                    >
                        {#snippet children()}
                            <div
                                class="rich-deskripsi text-md"
                                bind:this={kontenHost}
                            >
                                {@html konten}
                            </div>
                        {/snippet}
                        {#snippet fallback()}
                            <div class="detail-skeleton" aria-hidden="true">
                                <div class="detail-skeleton__bar detail-skeleton__bar--title"></div>
                                <div class="detail-skeleton__bar"></div>
                                <div class="detail-skeleton__bar"></div>
                                <div class="detail-skeleton__bar detail-skeleton__bar--short"></div>
                            </div>
                        {/snippet}
                    </WhenVisible>
                {:else if activeTab === 'deskripsi'}
                    <div class="rich-deskripsi text-md">
                        {@html materi.deskripsi}
                    </div>
                {:else}
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-12 col-md-7">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="detail-file-icon {fileStyle?.cssClass ??
                                        ''}"
                                >
                                    <i
                                        class={`bi ${fileStyle?.icon ?? 'bi-file-earmark-text'}`}
                                    ></i>
                                </div>
                                <div class="min-w-0">
                                    <div
                                        class="fw-semibold text-truncate mb-1"
                                    >
                                        {materi.file_name}
                                    </div>
                                    <div class="text-muted small">
                                        {formatBytes(materi.file_size)} · {formatFileType(
                                            materi.mime_type,
                                            materi.file_name,
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-5 text-md-end">
                            <a
                                href={unduhUrl}
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-download me-1"></i>Unduh Berkas
                            </a>
                        </div>
                    </div>

                    {#if viewerKind === 'pdf'}
                        <div class="detail-viewer-frame">
                            <PdfViewer url={lihatUrl} />
                        </div>
                    {:else if viewerKind === 'docx'}
                        <div class="detail-viewer-frame">
                            <DocxViewer url={lihatUrl} />
                        </div>
                    {:else if viewerKind === 'xlsx'}
                        <div class="detail-viewer-frame">
                            <XlsxViewer url={lihatUrl} />
                        </div>
                    {:else if viewerKind === 'pptx'}
                        <div class="detail-viewer-frame">
                            <PptxViewer url={lihatUrl} />
                        </div>
                    {:else}
                        <div class="text-center text-secondary py-4 border rounded-1">
                            <div
                                class="detail-file-icon detail-file-icon--muted mx-auto mb-3"
                            >
                                <i class="bi bi-file-earmark"></i>
                            </div>
                            <p class="mb-1">
                                Berkas berformat {formatFileType(
                                    materi.mime_type,
                                    materi.file_name,
                                )} tidak bisa dipratinjau di sini.
                            </p>
                            <p class="mb-0">
                                Gunakan tombol
                                <a
                                    href={unduhUrl}
                                    class="link-primary fw-semibold"
                                >
                                    Unduh Berkas
                                </a>
                                untuk membukanya.
                            </p>
                        </div>
                    {/if}
                {/if}
            </CardBody>
        </Card>
    {:else}
        <Card
            class="border rounded-top-0 shadow-none rounded-bottom-1 mt-0"
        >
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-inbox" style="font-size: 2.5rem"></i>
                    <p class="mt-3 mb-0">
                        Materi ini belum memiliki konten, deskripsi, atau lampiran.
                    </p>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>

<style>












    .detail-file-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: var(--bs-border-radius);
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .detail-file-icon--pdf {
        background: rgba(220, 53, 69, 0.08);
        color: #dc3545;
    }

    .detail-file-icon--word {
        background: rgba(15, 108, 189, 0.08);
        color: #0f6cbd;
    }

    .detail-file-icon--excel {
        background: rgba(33, 115, 70, 0.08);
        color: #217346;
    }

    .detail-file-icon--ppt {
        background: rgba(210, 71, 38, 0.08);
        color: #d24726;
    }

    .detail-file-icon--muted {
        background: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
    }

    .detail-viewer-frame {
        padding: 0.5rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        background: var(--bs-body-bg);
    }

    .detail-skeleton {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .detail-skeleton__bar {
        height: 0.875rem;
        border-radius: 0.375rem;
        background: linear-gradient(
            90deg,
            var(--bs-tertiary-bg) 25%,
            var(--bs-secondary-bg) 50%,
            var(--bs-tertiary-bg) 75%
        );
        background-size: 200% 100%;
        animation: detail-skeleton-pulse 1.4s ease-in-out infinite;
    }

    .detail-skeleton__bar--title {
        width: 45%;
        height: 1.25rem;
    }

    .detail-skeleton__bar--short {
        width: 65%;
    }

    @keyframes detail-skeleton-pulse {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .detail-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.75rem 1rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .detail-tabs .nav-link {
        border: 0;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        padding: 0.5rem 0.75rem;
        color: var(--bs-secondary-color);
        font-weight: 600;
        font-size: var(--bs-font-size-sm);
    }

    .detail-tabs .nav-link:hover {
        color: var(--bs-primary);
    }

    .detail-tabs .nav-link.active {
        color: var(--bs-primary);
        background: transparent;
        border-bottom-color: var(--bs-primary);
    }

    :global(.materi-header + .card) {
        border-top: 0;
    }

    :global(.rich-deskripsi p) {
        margin-bottom: 0.5rem;
    }

    :global(.rich-deskripsi p:last-child) {
        margin-bottom: 0;
    }

    :global(.rich-deskripsi ul),
    :global(.rich-deskripsi ol) {
        margin-bottom: 0.5rem;
        padding-left: 1.25rem;
    }

    :global(.rich-deskripsi h1),
    :global(.rich-deskripsi h2),
    :global(.rich-deskripsi h3),
    :global(.rich-deskripsi h4) {
        margin: 1.5rem 0 0.5rem;
        scroll-margin-top: 6rem;
    }

    :global(.rich-deskripsi h1:first-child),
    :global(.rich-deskripsi h2:first-child),
    :global(.rich-deskripsi h3:first-child) {
        margin-top: 0;
    }

    :global(.rich-deskripsi a) {
        color: var(--bs-primary);
    }

    :global(.rich-deskripsi iframe) {
        max-width: 100%;
        border: 0;
        border-radius: var(--bs-border-radius);
    }

    :global(.rich-deskripsi .youtube-embed) {
        display: flex;
        justify-content: center;
        margin: 0.75rem 0;
    }

    :global(.rich-deskripsi .audio-player) {
        width: 100%;
        margin: 0.75rem 0;
    }

    :global(.rich-deskripsi .daftar-isi) {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        background: var(--bs-tertiary-bg);
        padding: 0.75rem 1rem;
        margin: 0.75rem 0 1.5rem;
    }

    :global(.rich-deskripsi .daftar-isi__title) {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    :global(.rich-deskripsi .daftar-isi__list) {
        margin: 0;
        padding-left: 1.25rem;
    }

    :global(.rich-deskripsi .daftar-isi__item) {
        margin-bottom: 0.25rem;
    }

    :global(.rich-deskripsi .daftar-isi__item[data-level='1']) {
        margin-top: 0.3rem;
    }

    :global(.rich-deskripsi .daftar-isi__item[data-level='3']) {
        padding-left: 1rem;
    }

    :global(.rich-deskripsi .daftar-isi__link) {
        text-decoration: none;
    }

    :global(.rich-deskripsi pre) {
        position: relative;
        background: var(--bs-body-bg, #f6f8fa);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        padding: 0.75rem 1rem;
        margin: 0.75rem 0 1rem;
        overflow-x: auto;
    }

    :global(.rich-deskripsi pre code) {
        background: none;
        padding: 0;
        font-family: var(--bs-font-monospace);
        font-size: 0.875rem;
        line-height: 1.6;
    }

    :global(.rich-deskripsi pre::before) {
        content: attr(data-language);
        display: block;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bs-secondary-color);
        margin-bottom: 0.5rem;
    }

    :global(.rich-deskripsi code) {
        background: var(--bs-secondary-bg);
        border-radius: 0.25rem;
        padding: 0.1em 0.35em;
        font-size: 0.875em;
        font-family: var(--bs-font-monospace);
    }

    @media (max-width: 575.98px) {
        }
    /* Header bersih tanpa gradient */
    .materi-header {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.75rem;
        padding: 1.25rem;
    }
</style>
