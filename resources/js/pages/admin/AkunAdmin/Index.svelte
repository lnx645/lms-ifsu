<script lang="ts">
    import AkunAdminController from '@/actions/App/Http/Controllers/Admin/AkunAdminController';
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import { Badge } from '@sveltestrap/sveltestrap';

    type AkunAdminItem = Record<string, unknown> & { id: number };

    let {
        akunAdmin,
        filters: activeFilters = {},
    }: {
        akunAdmin: {
            data: AkunAdminItem[];
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            from?: number | null;
            to?: number | null;
        };
        filters?: Record<string, string>;
    } = $props();

    const columns: CrudColumn[] = [
        { key: 'name', label: 'Nama', cell: namaCell },
        { key: 'email', label: 'Email' },
        { key: 'is_admin', label: 'Peran', center: true, cell: peranCell },
        { key: 'created_at', label: 'Terdaftar', center: true },
    ];

    const fields: CrudField[] = [
        {
            name: 'name',
            label: 'Nama',
            placeholder: 'Contoh: Admin Sekolah',
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            placeholder: 'Contoh: admin@smkifsu.sch.id',
        },
        {
            name: 'password',
            label: 'Password',
            placeholder: 'Minimal 8 karakter (kosongkan saat edit jika tidak diganti)',
        },
        {
            name: 'role',
            label: 'Peran',
            type: 'select',
            options: [
                { value: 'admin', label: 'Administrator' },
                { value: 'panitia', label: 'Panitia Ujian' },
            ],
        },
    ];

    let items = $derived(akunAdmin?.data ?? []);
</script>

{#snippet namaCell(item)}
    <span class="fw-semibold">
        <i class="bi bi-shield-lock me-1 text-danger"></i>
        {item.name}
    </span>
{/snippet}

{#snippet peranCell(item)}
    {#if item.role === 'panitia'}
        <Badge color="warning" pill>Panitia Ujian</Badge>
    {:else}
        <Badge color="danger" pill>Administrator</Badge>
    {/if}
{/snippet}

<CrudManager
    title="Manajemen Akun Admin & Panitia"
    subtitle="Kelola akun administrator dan panitia ujian."
    {columns}
    {fields}
    {items}
    controller={AkunAdminController}
    resourceName="Akun Admin"
    createLabel="Tambah Akun Admin"
    searchable
    searchPlaceholder="Cari nama atau email admin…"
    pagination={akunAdmin}
    query={activeFilters}
    only={['akunAdmin']}
/>