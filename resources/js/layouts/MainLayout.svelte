<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Toaster } from 'svelte-sonner';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';

    let {
        children,
    }: {
        children: Snippet;
    } = $props();
</script>

<Toaster />
<div>
    {@render children?.()}
</div>

<ConfirmDialog />
