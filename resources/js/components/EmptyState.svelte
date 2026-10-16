<script lang="ts">
    let {
        icon = 'bi-inbox',
        message,
        hint = '',
        variant = 'plain',
    }: {
        icon?: string;
        message: string;
        hint?: string;
        variant?: 'plain' | 'card';
    } = $props();
</script>

<div
    class="text-center {variant === 'card'
        ? 'text-secondary py-5 border rounded bg-light'
        : 'text-muted py-5'}"
>
    <i class={`bi ${icon} display-5 d-block mb-2`}></i>
    <div>{message}</div>
    {#if hint}<p class="small mb-0 mt-1 text-muted">{hint}</p>{/if}
</div>
