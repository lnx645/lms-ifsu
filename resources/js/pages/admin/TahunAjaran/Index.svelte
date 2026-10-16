<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import TahunAjaranController from '@/actions/App/Http/Controllers/Admin/TahunAjaranController';
    import { Badge } from '@sveltestrap/sveltestrap';

    type TahunAjaranItem = Record<string, unknown> & { id: number };

    let {
        tahunAjarans,
        filters: activeFilters = {},
    }: {
        tahunAjarans: {
            data: TahunAjaranItem[];
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
        { key: 'name', label: 'Tahun Ajaran' },
        {
            key: 'active',
            label: 'Status',
            center: true,
            cell: statusCell,
        },
    ];

    const fields: CrudField[] = [
        {
            name: 'name',
            label: 'Tahun Ajaran',
            placeholder: 'Contoh: 2024/2025',
        },
        {
            name: 'active',
            label: 'Jadikan Aktif',
            type: 'checkbox',
            placeholder: 'Tahun ajaran aktif',
        },
    ];

    let items = $derived(tahunAjarans.data ?? []);
</script>

{#snippet statusCell(item)}
    {#if item.active}
        <Badge color="success" type="sm">Aktif</Badge>
    {:else}
        <Badge color="secondary" type="sm">Nonaktif</Badge>
    {/if}
{/snippet}

<CrudManager
    title="Tahun Ajaran"
    subtitle="Kelola tahun ajaran dan tandai yang sedang berjalan."
    {columns}
    {fields}
    {items}
    controller={TahunAjaranController}
    createLabel="Tambah Tahun Ajaran"
    resourceName="Tahun Ajaran"
    searchable
    searchPlaceholder="Cari tahun ajaran…"
    filters={[
        {
            name: 'active',
            label: 'Status',
            type: 'select',
            placeholder: 'Semua Status',
            options: [
                { value: '1', label: 'Aktif' },
                { value: '0', label: 'Nonaktif' },
            ],
        },
    ]}
    query={activeFilters}
    only={['tahunAjarans']}
    pagination={tahunAjarans}
/>
