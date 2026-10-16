<script lang="ts">
    import { init as initPptxPreview } from 'pptx-preview';
    //@ts-ignore
    import type { PPTXPreviewer } from 'pptx-preview';
    import { Spinner } from '@sveltestrap/sveltestrap';
    import { onDestroy, onMount } from 'svelte';

    let { url }: { url: string } = $props();

    let container: HTMLDivElement | undefined = $state();
    let loading = $state(true);
    let errorMessage = $state('');
    let zoom = $state(0.5);
    let ready = $state(false);
    let previewer: PPTXPreviewer | null = null;
    let data: ArrayBuffer | null = null;
    let baseWidth = 960;

    async function render() {
        if (!container || !data) {
            return;
        }

        previewer?.destroy();
        container.innerHTML = '';
        const width = Math.max(320, Math.round(baseWidth * zoom));
        const height = Math.round(width * 0.75);
        previewer = initPptxPreview(container, {
            mode: 'slide',
            width,
            height,
        });
        await previewer.preview(data);
    }

    $effect(() => {
        if (!ready) {
            return;
        }

        void zoom;
        render().catch(() => {
            errorMessage = 'Gagal memuat berkas PPTX.';
        });
    });

    onMount(async () => {
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error('fetch failed');
            }
            data = await response.arrayBuffer();
            const rect = container?.getBoundingClientRect();
            baseWidth = Math.max(
                640,
                Math.round((rect?.width ?? 960) * 2 - 32),
            );
            await render();
        } catch (error) {
            errorMessage = 'Gagal memuat berkas PPTX.';
        } finally {
            loading = false;
            ready = true;
        }
    });

    onDestroy(() => {
        previewer?.destroy();
        previewer = null;
    });
</script>

<div class="pptx-viewer">
    <div class="pptx-viewer__toolbar">
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={!ready || zoom <= 0.25}
                onclick={() => (zoom = Math.max(0.25, zoom - 0.25))}
                aria-label="Perkecil"
            >
                <i class="bi bi-zoom-out"></i>
            </button>
            <span class="pptx-viewer__zoom"
                >{Math.round(zoom * 100)}%</span
            >
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                disabled={!ready || zoom >= 2.5}
                onclick={() => (zoom = Math.min(2.5, zoom + 0.25))}
                aria-label="Perbesar"
            >
                <i class="bi bi-zoom-in"></i>
            </button>
        </div>
    </div>
    <div class="pptx-viewer__body" bind:this={container}>
        {#if loading}
            <div class="text-center text-secondary py-5">
                <Spinner color="primary" />
                <p class="mt-2 mb-0 small">Memuat berkas PPTX…</p>
            </div>
        {:else if errorMessage}
            <div class="alert alert-danger border rounded-1 m-3 mb-4">
                <i class="bi bi-exclamation-triangle me-2"
                ></i>{errorMessage}
            </div>
        {/if}
    </div>
</div>

<style>
    .pptx-viewer {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        overflow: hidden;
        background: var(--bs-secondary-bg);
    }

    .pptx-viewer__toolbar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 0.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }

    .pptx-viewer__zoom {
        font-size: 0.875rem;
        min-width: 3.5rem;
        text-align: center;
    }

    .pptx-viewer__body {
        max-height: 70vh;
        overflow: auto;
        padding: 1rem;
        display: flex;
        justify-content: center;
    }
</style>
