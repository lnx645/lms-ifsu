<script lang="ts">
    import { usePage } from '@inertiajs/svelte';
    import { Alert } from '@sveltestrap/sveltestrap';

    let { class: className = '' }: { class?: string } = $props();

    const tahunAjaranAktif = $derived(usePage().props.tahunAjaranAktif);
</script>

{#if tahunAjaranAktif}
    <Alert color="success" class={`d-flex align-items-center gap-2 ${className}`}>
        <i class="bi bi-calendar-check-fill"></i>
        <span>
            Tahun Ajaran Aktif: <strong>{tahunAjaranAktif.name}</strong>
        </span>
    </Alert>
{:else}
    <Alert color="warning" class={`d-flex align-items-center gap-2 ${className}`}>
        <i class="bi bi-calendar-x"></i>
        <span>Belum ada Tahun Ajaran yang aktif.</span>
    </Alert>
{/if}
