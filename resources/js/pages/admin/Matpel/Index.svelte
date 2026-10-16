<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import MatpelController from '@/actions/App/Http/Controllers/Admin/MatpelController';

    type MatpelItem = Record<string, unknown> & { id: number };

    let {
        matpels,
        filters: activeFilters = {},
    }: {
        matpels: {
            data: MatpelItem[];
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
        { key: 'name', label: 'Nama Mata Pelajaran' },
        {
            key: 'description',
            label: 'Deskripsi',
            format: (value) => (value ? String(value) : '—'),
        },
    ];

    const fields: CrudField[] = [
        {
            name: 'name',
            label: 'Nama Mata Pelajaran',
            placeholder: 'Contoh: Matematika',
        },
        {
            name: 'description',
            label: 'Deskripsi',
            type: 'textarea',
            placeholder: 'Keterangan singkat mata pelajaran (opsional)',
        },
    ];

    let items = $derived(matpels.data ?? []);
</script>

<CrudManager
    title="Mata Pelajaran"
    subtitle="Kelola daftar mata pelajaran yang diajarkan di sekolah."
    {columns}
    {fields}
    {items}
    controller={MatpelController}
    createLabel="Tambah Mata Pelajaran"
    resourceName="Mata Pelajaran"
    searchable
    searchPlaceholder="Cari nama mata pelajaran…"
    filters={[
        {
            name: 'has_description',
            label: 'Deskripsi',
            type: 'select',
            placeholder: 'Semua Deskripsi',
            options: [
                { value: '1', label: 'Dengan Deskripsi' },
                { value: '0', label: 'Tanpa Deskripsi' },
            ],
        },
    ]}
    query={activeFilters}
    only={['matpels']}
    pagination={matpels}
/>
