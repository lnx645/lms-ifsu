<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import SiswaController from '@/actions/App/Http/Controllers/Admin/SiswaController';
    import AkunSiswaController from '@/actions/App/Http/Controllers/Admin/AkunSiswaController';
    import SiswaKelasController from '@/actions/App/Http/Controllers/Admin/SiswaKelasController';
    import { Badge } from '@sveltestrap/sveltestrap';
    import { router, useForm } from '@inertiajs/svelte';
    import { Modal, ModalHeader, ModalBody, ModalFooter, Button, Input, Label } from '@sveltestrap/sveltestrap';
    import Avatar from '@/components/Avatar.svelte';

    let imporOpen = $state(false);
    const imporForm = useForm({ file: null as File | null });

    function kirimImpor(e: SubmitEvent) {
        e.preventDefault();
        if (!imporForm.file) return;
        imporForm.post(SiswaController.impor().url, {
            forceFormData: true,
            onSuccess: () => {
                imporOpen = false;
                imporForm.reset();
            },
        });
    }

    type SiswaItem = Record<string, unknown> & { id: string };

    let {
        siswa,
        filters: activeFilters = {},
        jenisKelaminOptions = [],
        tahunAjaranOptions = [],
        kelasOptions = [],
    }: {
        siswa: {
            data: SiswaItem[];
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            from?: number | null;
            to?: number | null;
        };
        filters?: Record<string, string>;
        jenisKelaminOptions?: { value: string; label: string }[];
        tahunAjaranOptions?: { id: number; name: string; active: boolean }[];
        kelasOptions?: { id: number; nama: string }[];
    } = $props();

    const columns: CrudColumn[] = [
        { key: 'nisn', label: 'NISN', badge: true },
        { key: 'nis', label: 'NIS', badge: true, badgeColor: 'secondary' },
        { key: 'nama_lengkap', label: 'Nama Lengkap', cell: namaCell },
        { key: 'jenis_kelamin', label: 'JK', center: true },
        { key: 'kelas', label: 'Kelas', cell: kelasCell },
        {
            key: 'tahun_ajaran',
            label: 'Tahun Ajaran',
            center: true,
            cell: tahunAjaranCell,
        },
        { key: 'punya_akun', label: 'Akun', center: true, cell: akunCell },
        { key: 'is_aktif', label: 'Status', center: true, cell: statusCell },
    ];

    const fields: CrudField[] = [
        {
            name: 'nisn',
            label: 'NISN',
            placeholder: 'Contoh: 0123456789',
            editable: false,
        },
        {
            name: 'nis',
            label: 'NIS',
            placeholder: 'Contoh: 1234567890',
        },
        {
            name: 'nama_lengkap',
            label: 'Nama Lengkap',
            placeholder: 'Contoh: Ahmad Badawi Syah Agus',
        },
        {
            name: 'tempat_lahir',
            label: 'Tempat Lahir',
            placeholder: 'Contoh: Bandung',
        },
        {
            name: 'tanggal_lahir',
            label: 'Tanggal Lahir',
            type: 'date',
        },
        {
            name: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            type: 'select',
            options: [
                { value: 'L', label: 'Laki-laki' },
                { value: 'P', label: 'Perempuan' },
            ],
        },
        {
            name: 'alamat',
            label: 'Alamat',
            type: 'textarea',
            placeholder: 'Ketikan alamat…',
        },
        {
            name: 'foto_profil',
            label: 'Foto',
            type: 'image',
            accept: 'image/*',
        },
        {
            name: 'is_aktif',
            label: 'Aktif',
            type: 'checkbox',
        },
    ];

    let items = $derived(siswa.data ?? []);
</script>

{#snippet statusCell(item)}
    {#if item.is_aktif}
        <Badge color="success">Aktif</Badge>
    {:else}
        <Badge color="danger">Nonaktif</Badge>
    {/if}
{/snippet}

{#snippet namaCell(item)}
    <div class="d-flex align-items-center gap-2">
        <Avatar
            src={item.foto_profil as string | null}
            name={item.nama_lengkap as string}
            size={36}
        />
        <span class="fw-semibold">{item.nama_lengkap}</span>
    </div>
{/snippet}

{#snippet akunCell(item)}
    {#if item.punya_akun}
        <Badge color="primary">
            <i class="bi bi-person-check me-1"></i>Ada
        </Badge>
    {:else}
        <Badge color="light" class="text-muted border">
            <i class="bi bi-person-x me-1"></i>Belum
        </Badge>
    {/if}
{/snippet}

{#snippet kelasCell(item)}
    {#if item.kelas}
        <span class="fw-semibold">
            <i class="bi bi-collection me-1 text-secondary"></i>
            {item.kelas.nama}
        </span>
    {:else}
        <span class="text-muted">Belum ada kelas</span>
    {/if}
{/snippet}

{#snippet tahunAjaranCell(item)}
    {#if item.tahun_ajaran}
        <Badge color="light" pill class="text-muted border"
            >{item.tahun_ajaran}</Badge
        >
    {:else}
        <span class="text-muted">-</span>
    {/if}
{/snippet}

<CrudManager
    title="Peserta Didik"
    subtitle="Kelola data peserta didik, akun, dan penempatan kelas."
    {columns}
    {fields}
    {items}
    controller={SiswaController}
    resourceName="Peserta Didik"
    createLabel="Tambah Peserta Didik"
    toolbarActions={[
        {
            key: 'impor',
            label: 'Impor Excel',
            icon: 'bi bi-file-earmark-excel',
            onClick: () => (imporOpen = true),
        },
        {
            key: 'template',
            label: 'Template',
            icon: 'bi bi-download',
            onClick: () => window.open(SiswaController.template().url, '_blank'),
        },
    ]}
    searchable
    searchPlaceholder="Cari nama, NISN, atau NIS…"
    pagination={siswa}
    query={activeFilters}
    only={['siswa', 'kelasOptions', 'tahunAjaranOptions']}
    onCreateSuccess={(created) => {
        if (created?.nisn) {
            router.visit(
                SiswaKelasController.index({
                    siswa: String(created.nisn),
                }).url,
            );
        }
    }}
    filters={[
        {
            name: 'tahun_ajaran',
            label: 'Tahun Ajaran',
            type: 'select',
            placeholder: 'Tahun Aktif',
            options: tahunAjaranOptions.map((t) => ({
                value: String(t.id),
                label: t.active ? `${t.name} (Aktif)` : t.name,
            })),
        },
        {
            name: 'kelas',
            label: 'Kelas',
            type: 'select',
            placeholder: 'Semua Kelas',
            options: kelasOptions.map((k) => ({
                value: String(k.id),
                label: k.nama,
            })),
        },
        {
            name: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            type: 'select',
            placeholder: 'Semua',
            options: jenisKelaminOptions,
        },
        {
            name: 'is_aktif',
            label: 'Status',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: '1', label: 'Aktif' },
                { value: '0', label: 'Nonaktif' },
            ],
        },
        {
            name: 'punya_kelas',
            label: 'Penempatan',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: '1', label: 'Sudah berkelas' },
                { value: '0', label: 'Belum berkelas' },
            ],
        },
        {
            name: 'punya_akun',
            label: 'Akun',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: '1', label: 'Sudah punya akun' },
                { value: '0', label: 'Belum punya akun' },
            ],
        },
    ]}
    actions={[
        {
            key: 'akun',
            label: 'Atur Akun',
            icon: 'bi-person-gear',
            size: 'sm',
            onClick(item) {
                router.visit(
                    AkunSiswaController.show({
                        siswa: item?.nisn as string,
                    }).url,
                );
            },
        },
        {
            key: 'kelas',
            label: 'Atur Kelas',
            icon: 'bi-collection',
            size: 'sm',
            onClick(item) {
                router.visit(
                    SiswaKelasController.index({
                        siswa: item?.nisn as string,
                    }).url,
                );
            },
        },
    ]}
/>

<Modal isOpen={imporOpen} toggle={() => (imporOpen = false)}>
    <ModalHeader toggle={() => (imporOpen = false)}>Impor Peserta Didik</ModalHeader>
    <form onsubmit={kirimImpor}>
        <ModalBody>
            <p class="text-muted small">
                Unggah file Excel (.xlsx) dengan kolom: <code>nisn, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir</code>.
                Password akun otomatis = NISN.
            </p>
            <div class="mb-3">
                <Label>File Excel</Label>
                <Input type="file" accept=".xlsx" onchange={(e) => (imporForm.file = e.currentTarget.files?.[0] ?? null)} />
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" type="button" onclick={() => (imporOpen = false)}>Batal</Button>
            <Button color="primary" type="submit" disabled={!imporForm.file || imporForm.processing}>
                {imporForm.processing ? 'Mengimpor...' : 'Impor'}
            </Button>
        </ModalFooter>
    </form>
</Modal>
