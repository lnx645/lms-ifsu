<script lang="ts">
    import type { Snippet } from 'svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';

    let { children }: { children: Snippet } = $props();
</script>

<!-- Layout minimal untuk halaman ujian: tanpa sidebar, tanpa bottom nav.
     Siswa fokus penuh ke soal. -->
<ConfirmDialog />
<div class="exam-layout min-vh-100 bg-body-tertiary">
    <main class="container py-3 py-md-4" style="max-width: 1140px">
        {@render children()}
    </main>
</div>

<style>
    .exam-layout {
        /* Cegah scroll horizontal yang tidak disengaja */
        overflow-x: hidden;
    }
</style>
