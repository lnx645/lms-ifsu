<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import JurusanController from '@/actions/App/Http/Controllers/Admin/JurusanController';
    import { Badge } from '@sveltestrap/sveltestrap';

    type JurusanItem = Record<string, unknown> & { id: number };

    let {
        jurusans,
        filters: activeFilters = {},
    }: {
        jurusans: {
            data: JurusanItem[];
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
        { key: 'kode', label: 'Kode', badge: true },
        { key: 'name', label: 'Nama Jurusan' },
        {
            key: 'jumlah_kelas',
            label: 'Kelas',
            center: true,
            cell: detailGUru,
        },
    ];

    const fields: CrudField[] = [
        { name: 'kode', label: 'Kode Jurusan', placeholder: 'Contoh: TKJ' },
        
        {
            name: 'name',
            label: 'Nama Jurusan',
            placeholder: 'Contoh: Teknik Komputer & Jaringan',
        },
    ];

    let items = $derived(jurusans.data ?? []);
</script>

{#snippet detailGUru(item)}
    <Badge color="primary" type="sm">{item?.jumlah_kelas}</Badge>
{/snippet}
<CrudManager
    title="Jurusan"
    subtitle="Kelola data jurusan yang tersedia di sekolah."
    {columns}
    {fields}
    {items}
    controller={JurusanController}
    createLabel="Tambah Jurusan"
    resourceName="Jurusan"
    searchable
    searchPlaceholder="Cari nama atau kode…"
    filters={[
        {
            name: 'has_kelas',
            label: 'Jumlah Kelas',
            type: 'select',
            placeholder: 'Semua Kelas',
            options: [
                { value: '1', label: 'Ada Kelas' },
                { value: '0', label: 'Belum Ada Kelas' },
            ],
        },
    ]}
    query={activeFilters}
    only={['jurusans']}
/>
