<script lang="ts">
    import { GlobalWorkerOptions, getDocument } from 'pdfjs-dist';
    import type { PDFDocumentProxy, RenderTask } from 'pdfjs-dist';
    import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
    import { Spinner } from '@sveltestrap/sveltestrap';
    import { onDestroy } from 'svelte';

    GlobalWorkerOptions.workerSrc = workerUrl;

    let { url, className = '' }: { url: string; className?: string } =
        $props();

    let canvas: HTMLCanvasElement | undefined = $state();
    let loading = $state(true);
    let errorMessage = $state('');
    let pageCount = $state(0);
    let currentPage = $state(1);
    let zoom = $state(1);
    let ready = $state(false);

    let documentProxy: PDFDocumentProxy | null = null;
    let renderTask: RenderTask | null = null;
    let renderToken = 0;

    $effect(() => {
        if (!ready || !canvas) {
            return;
        }

        const pageNumber = currentPage;
        const scale = zoom;
        const token = ++renderToken;
        renderTask?.cancel();

        const run = async () => {
            if (!documentProxy) {
                return;
            }

            if (!canvas) {
                return;
            }

            try {
                const page = await documentProxy.getPage(pageNumber);
                if (token !== renderToken) {
                    return;
                }

                const viewport = page.getViewport({ scale });
                const dpr = window.devicePixelRatio || 1;
                canvas.width = Math.floor(viewport.width * dpr);
                canvas.height = Math.floor(viewport.height * dpr);
                canvas.style.width = `${Math.floor(viewport.width)}px`;
                canvas.style.height = `${Math.floor(viewport.height)}px`;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return;
                }

                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                //@ts-ignore
                renderTask = page.render({
                    canvasContext: ctx,
                    viewport,
                });
                await renderTask.promise;

                if (token !== renderToken) {
                    return;
                }
                renderTask = null;
            } catch (error) {
                if (token === renderToken) {
                    errorMessage = 'Gagal merender halaman PDF.';
                }
            }
        };

        run();
    });

    onDestroy(() => {
        renderTask?.cancel();
        // pdfjs-dist's PDFDocumentProxy type does not expose a `destroy` method
        // in the bundled TypeScript definitions. The runtime object does
        // provide a `destroy` method, so we cast to `any` to avoid a type
        // error while still calling the method if it exists.
        (documentProxy as any)?.destroy();
        documentProxy = null;
    });

    async function loadDocument() {
        try {
            const task = getDocument({ url, withCredentials: true });
            documentProxy = await task.promise;
            pageCount = documentProxy.numPages;
            currentPage = 1;
            loading = false;
            ready = true;
        } catch (error) {
            errorMessage = 'Gagal memuat berkas PDF.';
            loading = false;
        }
    }

    loadDocument();
</script>

<div class={`pdf-viewer ${className}`}>
    <div class="pdf-viewer__toolbar">
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={currentPage <= 1}
                onclick={() => currentPage--}
                aria-label="Halaman sebelumnya"
            >
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="pdf-viewer__page-info">
                Halaman {currentPage} / {pageCount}
            </span>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={currentPage >= pageCount}
                onclick={() => currentPage++}
                aria-label="Halaman berikutnya"
            >
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={zoom <= 0.5}
                onclick={() => (zoom = Math.max(0.5, zoom - 0.25))}
                aria-label="Perkecil"
            >
                <i class="bi bi-zoom-out"></i>
            </button>
            <span class="pdf-viewer__zoom"
                >{Math.round(zoom * 100)}%</span
            >
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={zoom >= 3}
                onclick={() => (zoom = Math.min(3, zoom + 0.25))}
                aria-label="Perbesar"
            >
                <i class="bi bi-zoom-in"></i>
            </button>
        </div>
    </div>
    <div class="pdf-viewer__body">
        {#if loading}
            <div class="text-center text-secondary py-5">
                <Spinner color="primary" />
                <p class="mt-2 mb-0 small">Memuat berkas PDF…</p>
            </div>
        {:else if errorMessage}
            <div class="alert alert-danger border rounded-1 m-3 mb-4">
                <i class="bi bi-exclamation-triangle me-2"
                ></i>{errorMessage}
            </div>
        {:else}
            <canvas class="pdf-viewer__canvas" bind:this={canvas}
            ></canvas>
        {/if}
    </div>
</div>

<style>
    .pdf-viewer {
        display: flex;
        flex-direction: column;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        overflow: hidden;
        background: var(--bs-body-bg);
    }

    .pdf-viewer__toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }

    .pdf-viewer__page-info,
    .pdf-viewer__zoom {
        font-size: 0.875rem;
        min-width: 5.5rem;
        text-align: center;
    }

    .pdf-viewer__body {
        max-height: 70vh;
        overflow: auto;
        background: var(--bs-secondary-bg);
    }

    .pdf-viewer__canvas {
        display: block;
        margin: 0 auto;
        box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
