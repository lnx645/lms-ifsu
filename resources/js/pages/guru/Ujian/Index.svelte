<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import {
        Badge, Button, Card, CardBody,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import { confirm } from '@/lib/confirm.svelte';

    type UjianItem = {
        id: number;
        judul: string;
        kategori: 'kuis' | 'uh';
        kelas: string | null;
        matpel: string | null;
        status: 'draft' | 'terbit';
        durasi_menit: number;
        tanggal_mulai: string | null;
        jumlah_soal: number;
        jumlah_selesai: number;
    };

    let { ujians, penugasan = [], filters = { kategori: null, q: '' }, editUjian = null }: {
        ujians: { data: UjianItem[]; meta: { current_page: number; last_page: number; total: number; from?: number; to?: number } };
        penugasan: { value: number; label: string }[];
        filters: { kategori: string | null; q: string };
        editUjian?: any;
    } = $props();

    let q = $state(filters.q ?? '');
    let kategori = $state(filters.kategori ?? '');


    function terapkanFilter() {
        router.get('/app/guru/ujian', { q, kategori: kategori || null }, { preserveState: true, replace: true });
    }

    async function hapus(id: number, judul: string) {
        const ok = await confirm.show({
            title: 'Hapus Ujian',
            message: `Hapus "${judul}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(`/app/guru/ujian/${id}`, { preserveScroll: true });
    }

    function toggleTerbit(item: UjianItem) {
        router.post(`/app/guru/ujian/${item.id}/terbit`, {}, { preserveScroll: true });
    }
</script>

<svelte:head>
    <title>Ujian & Kuis</title>
</svelte:head>

<PageHeader title="Ujian & Kuis" subtitle="Kuis dan Ulangan Harian">
    {#snippet actions()}
        <a use:inertia href="/app/guru/ujian/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Buat Baru
        </a>
    {/snippet}
</PageHeader>

<!-- Filter -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn {kategori === '' ? 'btn-primary' : 'btn-outline-primary'}" onclick={() => { kategori = ''; terapkanFilter(); }}>Semua</button>
        <button type="button" class="btn {kategori === 'kuis' ? 'btn-primary' : 'btn-outline-primary'}" onclick={() => { kategori = 'kuis'; terapkanFilter(); }}>Kuis</button>
        <button type="button" class="btn {kategori === 'uh' ? 'btn-primary' : 'btn-outline-primary'}" onclick={() => { kategori = 'uh'; terapkanFilter(); }}>Ulangan Harian</button>
    </div>
    <div class="input-group input-group-sm" style="max-width: 280px;">
        <input class="form-control" placeholder="Cari judul..." bind:value={q} onkeydown={(e) => { if (e.key === 'Enter') terapkanFilter(); }} />
        <button class="btn btn-outline-secondary" onclick={terapkanFilter}><i class="bi bi-search"></i></button>
    </div>
</div>

<!-- Daftar -->
{#if !ujians || !ujians.data || ujians.data.length === 0}
    <EmptyState message="Belum ada kuis atau ulangan harian" hint="Klik 'Buat Baru' untuk membuat." />
{:else}
    <div class="row g-3">
        {#each ujians.data as u}
            <div class="col-12 col-md-6 col-lg-4">
                <Card class="h-100">
                    <CardBody>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <Badge color={u.kategori === 'kuis' ? 'info' : 'secondary'}>
                                {u.kategori === 'kuis' ? 'Kuis' : 'Ulangan Harian'}
                            </Badge>
                            <Badge color={u.status === 'terbit' ? 'success' : 'warning'}>
                                {u.status === 'terbit' ? 'Terbit' : 'Draft'}
                            </Badge>
                        </div>
                        <h6 class="mb-1">{u.judul}</h6>
                        <div class="small text-muted mb-2">
                            {#if u.matpel}{u.matpel} • {/if}{#if u.kelas}{u.kelas}{/if}
                        </div>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-clock me-1"></i>{u.durasi_menit} menit
                            <span class="mx-1">•</span>
                            <i class="bi bi-list-check me-1"></i>{u.jumlah_soal} soal
                            {#if u.tanggal_mulai}
                                <span class="mx-1">•</span>
                                <i class="bi bi-calendar me-1"></i>{u.tanggal_mulai}
                            {/if}
                        </div>
                        <div class="d-flex gap-1 flex-wrap">
                            <a use:inertia href="/app/guru/ujian/{u.id}/soal" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-list-task me-1"></i>Soal
                            </a>
                            <a use:inertia href="/app/guru/ujian/{u.id}/hasil" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-bar-chart me-1"></i>Hasil ({u.jumlah_selesai})
                            </a>
                            <a use:inertia href="/app/guru/ujian/{u.id}/edit" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick={() => toggleTerbit(u)}>
                                {u.status === 'terbit' ? 'Tarik' : 'Terbitkan'}
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick={() => hapus(u.id, u.judul)}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
    {#if ujians?.meta && ujians.meta.last_page > 1}
        <div class="mt-3">
            <Pagination
                meta={ujians.meta}
                onPageChange={(p) => router.get('/app/guru/ujian', { ...filters, page: p }, { preserveState: true })}
            />
        </div>
    {/if}
{/if}

