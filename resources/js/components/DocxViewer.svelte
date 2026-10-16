<script lang="ts">
    import { renderAsync } from 'docx-preview';
    import { Spinner } from '@sveltestrap/sveltestrap';
    import { onDestroy, onMount } from 'svelte';

    let { url }: { url: string } = $props();

    let container: HTMLDivElement | undefined = $state();
    let loading = $state(true);
    let errorMessage = $state('');

    onMount(async () => {
        if (!container) {
            loading = false;
            return;
        }
        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error('fetch failed');
            }
            const data = await response.arrayBuffer();
            await renderAsync(data, container, undefined, {
                inWrapper: true,
            });
        } catch (error) {
            errorMessage = 'Gagal memuat berkas DOCX.';
        } finally {
            loading = false;
        }
    });

    onDestroy(() => {
        if (container) {
            container.innerHTML = '';
        }
    });
</script>

<div class="docx-viewer">
    <div class="docx-viewer__body" bind:this={container}>
        {#if loading}
            <div class="text-center text-secondary py-5">
                <Spinner color="primary" />
                <p class="mt-2 mb-0 small">Memuat berkas DOCX…</p>
            </div>
        {:else if errorMessage}
            <div class="alert alert-danger border rounded-1 m-3 mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>{errorMessage}
            </div>
        {/if}
    </div>
</div>

<style>
    .docx-viewer {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        overflow: hidden;
        background: var(--bs-secondary-bg);
    }

    .docx-viewer__body {
        max-height: 70vh;
        overflow: auto;
        padding: 1rem;
        background: var(--bs-body-bg);
    }

    .docx-viewer__body :global(.docx-wrapper) {
        background: var(--bs-body-bg);
    }
</style>