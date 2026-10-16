# CrudManager

Komponen Svelte generik untuk membuat halaman CRUD (Create, Read, Update, Delete) dengan cepat.
Lokasi: `resources/js/components/crud/CrudManager.svelte`.

Komponen ini menangani: tabel data, modal form (create/edit), konfirmasi hapus global,
infinite scroll (`WhenVisible`), field berbagai tipe (text, select, image, dll), kolom custom
(snippet atau component), serta action button baik per-baris maupun di toolbar.

---

## Cara Penggunaan Dasar

```svelte
<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import JurusanController from '@/actions/App/Http/Controllers/Admin/JurusanController';
    import { WhenVisible } from '@inertiajs/svelte';

    let { jurusans }: {
        jurusans: {
            data: Array<Record<string, unknown> & { id: number }>;
            current_page: number;
            last_page: number;
        };
    } = $props();

    const columns: CrudColumn[] = [
        { key: 'kode', label: 'Kode', badge: true },
        { key: 'name', label: 'Nama Jurusan' },
    ];

    const fields: CrudField[] = [
        { name: 'kode', label: 'Kode Jurusan', placeholder: 'TKJ' },
        { name: 'name', label: 'Nama Jurusan' },
    ];

    let items = $derived(jurusans.data ?? []);
</script>

<CrudManager
    title="Jurusan"
    subtitle="Kelola data jurusan."
    {columns}
    {fields}
    {items}
    controller={JurusanController}
    resourceName="Jurusan"
    createLabel="Tambah Jurusan"
/>

{#if jurusans.current_page < jurusans.last_page}
    <WhenVisible
        data="jurusans"
        params={{ data: { page: jurusans.current_page + 1 } }}
        always
    >
        {#snippet children({ fetching })}
            {#if fetching}
                <div class="text-center text-muted small py-3">
                    <span class="spinner-border spinner-border-sm me-2"></span> Memuat…
                </div>
            {/if}
        {/snippet}
    </WhenVisible>
{/if}
```

Controller di sisi backend cukup mengembalikan prop `jurusans` (paginator) dan route
`store` / `update` / `destroy`. Wayfinder action (`JurusanController`) sudah cocok sebagai
`controller`.

---

## Props `CrudManager`

| Prop            | Tipe                                 | Default         | Keterangan |
| --------------- | ------------------------------------ | --------------- | ---------- |
| `title`         | `string`                             | `''`            | Judul halaman. |
| `subtitle`      | `string`                             | `''`            | Subjudul di bawah title. |
| `columns`       | `CrudColumn[]`                       | `[]`            | Definisi kolom tabel. |
| `fields`        | `CrudField[]`                        | `[]`            | Definisi input form. |
| `items`         | `CrudItem[]`                         | `[]`            | Baris data (`CrudItem = Record<string, unknown> & { id: number }`). |
| `controller`    | `CrudController`                     | wajib           | Objek dengan `store`/`update`/`destroy`. |
| `resourceName`  | `string`                             | `title \|\| 'data'` | Nama resource untuk teks konfirmasi. |
| `createLabel`   | `string`                             | `'Tambah'`      | Teks tombol create. |
| `emptyText`     | `string`                             | `'Belum ada data.'` | Teks saat tabel kosong. |
| `actions`       | `CrudAction[]`                       | `[]`            | Tombol custom per baris (sebelum Edit/Delete). |
| `toolbarActions`| `CrudAction[]`                       | `[]`            | Tombol custom di toolbar (sebelum tombol Tambah). |

---

## `CrudColumn`

```ts
export type CrudColumn = {
    key: string;
    label: string;
    center?: boolean;
    badge?: boolean;
    badgeColor?: string;
    format?: (value: unknown, item: CrudItem) => string;
    cell?: Snippet<[CrudItem]>;
    cellComponent?: Component<{ item: CrudItem }>;
};
```

- `key` — nama property di `item` yang ditampilkan (mode default).
- `center` — ratakan tengah.
- `badge` — tampilkan sebagai `<Badge>` (warna via `badgeColor`, default `info`).
- `format` — fungsi untuk mengubah nilai teks sebelum ditampilkan.
- `cell` — **snippet** untuk render custom per baris (mis. gambar). Prioritas tertinggi.
- `cellComponent` — **component** untuk render custom per baris.

**Urutan prioritas render sel:** `cell` → `cellComponent` → `badge` → `format`/`display`.

### Contoh `cell` (snippet)

```svelte
<script lang="ts">
    const columns: CrudColumn[] = [
        { key: 'logo', label: 'Logo', cell: logoCell },
    ];
</script>

{#snippet logoCell(item)}
    {#if item.logo}
        <img src={item.logo} class="img-thumbnail" style="height:40px" alt="" />
    {:else}
        <span class="text-muted">—</span>
    {/if}
{/snippet}
```

### Contoh `cellComponent`

```svelte
<script lang="ts">
    import JurusanCell from '@/components/crud/JurusanCell.svelte';
    const columns: CrudColumn[] = [
        { key: 'logo', label: 'Logo', cellComponent: JurusanCell },
    ];
</script>
```

`resources/js/components/crud/JurusanCell.svelte`:
```svelte
<script lang="ts">
    import type { CrudItem } from '@/components/crud/CrudManager.svelte';
    let { item }: { item: CrudItem } = $props();
</script>
<img src={item.logo} class="img-thumbnail" style="height:40px" alt="" />
```

---

## `CrudField`

```ts
export type CrudField = {
    name: string;
    label: string;
    type?: 'text' | 'email' | 'number' | 'textarea' | 'select' | 'date' | 'file' | 'image';
    placeholder?: string;
    options?: { value: string | number; label: string }[];
    accept?: string;
    required?: boolean;
    editable?: boolean;
    multiple?: boolean;
    size?: 'xs' | 'sm' | 'lg';
};
```

- `type` — jenis input. Default `text`.
- `options` — pilihan untuk `type: 'select'`.
- `accept` — atribut `accept` untuk `file`/`image` (default `image/*` untuk `image`).
- `editable: false` — **field tidak bisa diubah saat mode Edit** (tetap bisa diisi saat Tambah).
- `multiple` — aktifkan multi-select (nilai awal otomatis `[]`, kirim array).
- `size` — ukuran select sesuai Bootstrap (`xs`/`sm`/`lg`).

Untuk `select`, nilai yang dikirim ke backend adalah `value` mentah (bukan object), baik
single maupun multiple, berkat `getOptionValue` bawaan.

### Contoh field lengkap

```ts
const fields: CrudField[] = [
    { name: 'kode', label: 'Kode', placeholder: 'TKJ' },
    { name: 'name', label: 'Nama Jurusan' },
    {
        name: 'status', label: 'Status', type: 'select', size: 'sm',
        options: [
            { value: 'aktif', label: 'Aktif' },
            { value: 'nonaktif', label: 'Nonaktif' },
        ],
    },
    {
        name: 'mapel', label: 'Mata Pelajaran', type: 'select', multiple: true,
        options: [
            { value: 1, label: 'Matematika' },
            { value: 2, label: 'Bahasa Inggris' },
        ],
    },
    { name: 'kode', label: 'Kode', editable: false }, // tidak bisa diubah saat edit
    { name: 'logo', label: 'Logo', type: 'image' },    // upload gambar
];
```

> **Upload file/image:** `CrudManager` mengirim `File` lewat form (Inertia otomatis
> mengubah menjadi FormData). Menyimpan ke storage & menyimpan path-nya adalah tanggung
> jawab controller (`store`/`update`).

---

## `CrudAction`

```ts
export type CrudAction = {
    key: string;            // wajib, untuk keyed {#each}
    label?: string;        // teks tombol (opsional, tampil di samping icon)
    icon?: string;         // class bootstrap icon, mis. 'bi bi-eye'
    color?: string;        // warna sveltestrap (default 'light')
    class?: string;        // class tambahan
    size?: 'sm' | 'lg';    // ukuran tombol
    onClick?: (item?: CrudItem) => void;
};
```

- **Row action** (`actions`): `onClick(item)` menerima baris saat diklik.
- **Toolbar action** (`toolbarActions`): `onClick()` dipanggil tanpa argumen.
- `onClick` opsional — tombol boleh tanpa handler (mis. placeholder/navigasi manual).

### Contoh `actions` (per baris)

```svelte
<CrudManager
    ...
    actions={[
        {
            key: 'lihat',
            icon: 'bi bi-eye',
            onClick: (item) => router.visit(`/admin/jurusan/${item.id}`),
        },
        {
            key: 'nonaktifkan',
            label: 'Nonaktifkan',
            icon: 'bi bi-slash-circle',
            color: 'warning',
            onClick: (item) => router.post(`/admin/jurusan/${item.id}/nonaktif`),
        },
    ]}
/>
```

### Contoh `toolbarActions` (di header)

```svelte
<CrudManager
    ...
    toolbarActions={[
        { key: 'import', label: 'Import', icon: 'bi bi-upload', onClick: () => importFile() },
        { key: 'export', label: 'Export', icon: 'bi bi-download', onClick: () => router.get('/admin/jurusan/export') },
        { key: 'cetak', icon: 'bi bi-printer', onClick: () => window.print() },
    ]}
/>
```

Tombol custom selalu muncul **sebelum** tombol Edit/Delete (row) dan sebelum tombol
Tambah (toolbar).

---

## Controller (`CrudController`)

```ts
type CrudController = {
    store: (options?: any) => { url: string; method: string };
    update: (args: any) => { url: string; method: string };
    destroy: (args: any) => { url: string; method: string };
};
```

Wayfinder action (mis. `JurusanController` hasil `php artisan wayfinder:generate`) sudah
memenuhi bentuk ini:

- `store()` → `POST /admin/jurusan`
- `update(item)` → `PUT /admin/jurusan/{id}` (item harus punya `id`)
- `destroy(item)` → `DELETE /admin/jurusan/{id}` (item harus punya `id`)

`CrudManager` secara otomatis memanggil `controller.update(item)` / `controller.destroy(item)`
dengan `item` dari baris yang sedang diedit/dihapus.

### Backend (Laravel) — contoh

```php
public function index()
{
    $jurusans = Jurusan::orderBy('name')->paginate(10)
        ->through(fn ($j) => [
            'id' => $j->id,
            'name' => $j->name,
            'kode' => $j->kode,
            'jumlah_kelas' => $j->kelas()->count(),
        ]);

    return Inertia::render('admin/Jurusan/Index', [
        'jurusans' => Inertia::merge($jurusans), // penting untuk infinite scroll
    ]);
}

public function store(Request $request)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'kode' => ['required', 'string', 'unique:jurusans,kode'],
    ]);
    Jurusan::create($data);
    Inertia::flash('toast', ['type' => 'success', 'message' => 'Berhasil disimpan.']);
    return Redirect::route('admin.jurusan.index');
}
```

> `Inertia::merge($jurusans)` wajib agar `WhenVisible` bisa **menambahkan** data (append),
> bukan mengganti, saat memuat halaman berikutnya.

---

## Infinite Scroll (`WhenVisible`)

Letakkan `<WhenVisible>` di bawah `<CrudManager>` dan hubungkan ke prop paginator:

```svelte
{#if jurusans.current_page < jurusans.last_page}
    <WhenVisible
        data="jurusans"
        params={{ data: { page: jurusans.current_page + 1 } }}
        always
    >
        {#snippet children({ fetching })}
            {#if fetching}
                <div class="text-center text-muted small py-3">Memuat…</div>
            {/if}
        {/snippet}
    </WhenVisible>
{/if}
```

- `data="jurusans"` — prop yang di-reload (hanya prop ini yang diambil dari server).
- `params={{ data: { page: ... } }}` — query param halaman berikutnya.
- `always` — terus memuat selama sentinel masih terlihat (sentinel otomatis hilang saat
  `current_page === last_page` karena guard `{#if}` di atas).

---

## Konfirmasi Hapus (Global)

Hapus tidak lagi pakai modal inline, melainkan dialog global `ConfirmDialog` yang bisa
dipakai di mana saja:

```ts
import { confirm } from '@/lib/confirm.svelte';

const ok = await confirm.show({
    title: 'Hapus Jurusan?',
    message: 'Data akan dihapus secara permanen.',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    color: 'danger',
    icon: 'bi bi-trash',
});

if (ok) {
    // lakukan aksi
}
```

`confirm.show()` mengembalikan `Promise<boolean>` (`true` = konfirmasi, `false` = batal).
Dialog menampilkan animasi Lottie (`warning-animate.json`) dan sudah di-mount global di
`AppShellLayout` & `MainLayout`, sehingga tersedia di semua halaman.

---

## Tips

- Pakai `format` di kolom untuk transformasi teks sederhana; pakai `cell`/`cellComponent`
  bila butuh markup (gambar, badge custom, link).
- Field `editable: false` hanya mengunci saat **Edit**; saat **Tambah** tetap bisa diisi.
- Untuk select yang datanya dari server, isi `options` dari props page (bukan hardcode).
- Pastikan setiap baris `items` memiliki `id` agar `update`/`destroy` langsung bekerja.
