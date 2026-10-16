<script lang="ts">
    import { isRole } from '@/lib/utils';
    import { usePage } from '@inertiajs/svelte';

    let { role,children } = $props();

    let {
        props: {
            auth: {
                user,
            },
        },
    } = usePage();
</script>

{#if isRole(user,role)}
   {@render children?.()}
{/if}
