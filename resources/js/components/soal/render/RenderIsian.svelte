<script lang="ts">
    // Renderer: Isian Singkat / Uraian
    let {
        jawaban = $bindable(''),
        disabled = false,
        rows = 1,
        placeholder = 'Tulis jawaban...',
    }: {
        jawaban?: string;
        disabled?: boolean;
        rows?: number;
        placeholder?: string;
    } = $props();
</script>

{#if rows > 1}
    <textarea class="form-control" rows={rows} bind:value={jawaban} disabled={disabled} placeholder={placeholder}></textarea>
{:else}
    <input class="form-control" bind:value={jawaban} disabled={disabled} placeholder={placeholder} />
{/if}
