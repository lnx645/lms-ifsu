<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import SoalRenderer from '@/components/soal/render/SoalRenderer.svelte';
    import Latex from '@/components/Latex.svelte';

    type Soal = {
        id: number; tipe: string; tipe_label: string; pertanyaan: string;
        detail: Record<string, any> | null;
    };

    let { kelompok, grouped }: {
        kelompok: { id: number; nama: string; kode?: string | null; matpel?: string };
        grouped: { tipe: string; tipe_label: string; soals: Soal[] }[];
    } = $props();

    let showKunci = $state(false);
    const totalSoal = $derived(grouped.reduce((s, g) => s + g.soals.length, 0));
    let nomor = 0;
</script>

<svelte:head>
    <title>Preview — {kelompok.nama}</title>
</svelte:head>

<PageHeader title="Preview Bank Soal" subtitle="{kelompok.nama} · {totalSoal} soal">
    {#snippet actions()}
        <Button size="sm" color={showKunci ? 'warning' : 'outline-warning'} onclick={() => (showKunci = !showKunci)}>
            <i class="bi bi-key me-1"></i>{showKunci ? 'Sembunyikan' : 'Tampilkan'} Kunci
        </Button>
        <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    {/snippet}
</PageHeader>

{#if grouped.length === 0}
    <EmptyState message="Belum ada soal" hint="Tambah soal dulu untuk melihat preview." />
{:else}
    {#each grouped as group}
        <div class="mb-2 mt-4 d-flex align-items-center gap-2">
            <span class="badge bg-primary">{group.tipe_label}</span>
            <span class="text-muted small">{group.soals.length} soal</span>
            <hr class="flex-grow-1 my-0" />
        </div>

        {#each group.soals as soal}
            {@const n = (nomor += 1)}
            <Card class="mb-3">
                <CardBody>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-secondary">Soal {n}</span>
                        <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}/soal/{soal.id}/edit"
                            class="btn btn-sm btn-link p-0 text-decoration-none" title="Edit soal">
                            <i class="bi bi-pencil me-1"></i><span class="small">Edit</span>
                        </a>
                    </div>
                    <div class="mb-3"><Latex teks={soal.pertanyaan} /></div>
                    <SoalRenderer tipe={soal.tipe} detail={soal.detail} disabled={false} showKunci={showKunci} />
                </CardBody>
            </Card>
        {/each}
    {/each}
{/if}
