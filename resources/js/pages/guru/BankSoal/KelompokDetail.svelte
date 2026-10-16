<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import RichTextEditor from '@/components/RichTextEditor.svelte';

    type Soal = {
        id: number; tipe: string; tipe_label?: string; pertanyaan: string;
        opsi?: { id: number; teks: string; benar: boolean }[];
    };

    let { kelompok }: {
        kelompok: {
            id: number; nama: string; deskripsi?: string;
            matpel?: { name: string };
            soals: Soal[];
        };
    } = $props();

    let konfirmHapusId = $state<number | null>(null);


    function hapusSoal(id: number) {
        router.delete(`/app/guru/bank-soal/kelompok/${kelompok.id}/soal/${id}`, {
            onSuccess: () => (konfirmHapusId = null),
        });
    }

    function hapusBank() {
        if (!confirm(`Hapus bank "${kelompok.nama}" beserta semua soalnya?`)) return;
        router.delete(`/app/guru/bank-soal/kelompok/${kelompok.id}`);
    }
</script>

<PageHeader title={kelompok.nama} subtitle="{kelompok.matpel?.name ?? ''} · {kelompok.soals.length} soal">
    {#snippet actions()}
        <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}/preview" class="btn btn-info btn-sm">
            <i class="bi bi-eye me-1"></i>Preview
        </a>
        <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}/soal/tambah" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Soal
        </a>
        <Button color="danger" outline onclick={hapusBank}>
            <i class="bi bi-trash me-1"></i>
        </Button>
    {/snippet}
</PageHeader>

{#if kelompok.deskripsi}<p class="text-muted">{kelompok.deskripsi}</p>{/if}

{#if kelompok.soals.length === 0}
    <EmptyState message="Bank masih kosong" hint="Klik 'Tambah Soal' untuk mengisi soal pertama." />
{:else}
    <div class="row g-2">
        {#each kelompok.soals as s, i}
            <div class="col-12">
                <Card>
                    <CardBody class="py-2">
                        <div class="d-flex gap-2 align-items-start">
                            <span class="fw-bold text-muted">{i + 1}.</span>
                            <div class="flex-grow-1">
                                <div class="d-flex gap-2 align-items-center mb-1">
                                    <Badge color="secondary">{s.tipe}</Badge>
                                    <span class="text-muted small">{s.poin} poin</span>
                                </div>
                                <div class="small">{@html s.pertanyaan}</div>
                                {#if s.opsi && s.opsi.length > 0}
                                    <div class="mt-1 small">
                                        {#each s.opsi as o}
                                            <div class={o.benar ? 'text-success fw-semibold' : 'text-muted'}>
                                                • {o.teks} {o.benar ? '✓' : ''}
                                            </div>
                                        {/each}
                                    </div>
                                {/if}
                            </div>
                            <div class="d-flex flex-column gap-1 ms-2">
                                <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}/soal/{s.id}/edit" class="btn btn-sm btn-outline-primary" title="Edit soal">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                {#if konfirmHapusId === s.id}
                                    <Button size="sm" color="danger" onclick={() => hapusSoal(s.id)} title="Ya, hapus">
                                        <i class="bi bi-check-lg"></i>
                                    </Button>
                                    <Button size="sm" color="outline-secondary" onclick={() => (konfirmHapusId = null)} title="Batal">
                                        <i class="bi bi-x-lg"></i>
                                    </Button>
                                {:else}
                                    <Button size="sm" color="outline-danger" onclick={() => (konfirmHapusId = s.id)} title="Hapus soal">
                                        <i class="bi bi-trash"></i>
                                    </Button>
                                {/if}
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
{/if}

