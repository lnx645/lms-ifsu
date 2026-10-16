<script lang="ts">
    let {
        value = null,
        onchange,
        label = '',
    }: {
        value?: string | null;
        onchange?: (val: string | null) => void;
        label?: string;
    } = $props();

    let display = $state(value ?? '');
</script>

{#if label}
    <label class="form-label">{label}</label>
{/if}
<input
    type="text"
    class="form-control"
    value={display}
    onchange={(e) => {
        display = (e.currentTarget as HTMLInputElement).value;
        onchange?.(display || null);
    }}
/>