<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        subtitle = '',
        actions,
    }: {
        title: string;
        subtitle?: string;
        actions?: Snippet;
    } = $props();
</script>

<div
    class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-3"
>
    <div>
        <h1 class="h4 mb-1 fw-semibold text-body">{title}</h1>
        {#if subtitle}
            <p class="text-muted small mb-0">{subtitle}</p>
        {/if}
    </div>
    {#if actions}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {@render actions()}
        </div>
    {/if}
</div>
