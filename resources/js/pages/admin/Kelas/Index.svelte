<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import {
        Modal,
        ModalHeader,
        ModalBody,
        ModalFooter,
        FormGroup,
        Label,
        Input,
        Button,
    } from '@sveltestrap/sveltestrap';
    import Select from '@/components/Select.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import ToggleSwitch from '@/components/ToggleSwitch.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import KelasController from '@/actions/App/Http/Controllers/Admin/KelasController';
    import { extractId } from '@/lib/utils';
    import type {
        GuruRef,
        KelasListRow,
        KelasTreeNode,
        SelectOption,
    } from '@/types/models';

    let {
        kelas_parent = [],
        kelas_list = [],
        jurusans = [],
        gurus = [],
    }: {
        kelas_parent?: KelasTreeNode[];
        kelas_list?: KelasListRow[];
        jurusans?: { id: number; name: string; kode: string | null }[];
        gurus?: GuruRef[];
    } = $props();

    const jurusanOptions = $derived<SelectOption[]>(
        jurusans.map((j) => ({ value: j.id, label: j.name })),
    );
    const guruOptions = $derived<SelectOption[]>(
        gurus.map((g) => ({ value: g.id, label: g.nama_lengkap })),
    );

    let editingId = $state<number | null>(null);
    let modalOpen = $state(false);
    let query = $state('');
    let collapsedMap = $state<Record<number, boolean>>({});

    const hasQuery = $derived(query.trim().length > 0);

    const parentGroups = $derived.by(() => {
        const list = kelas_list ?? [];
        const childrenOf = new Map<number | null, KelasListRow[]>();
        for (const k of list) {
            const pid = k.parent_id;
            if (!childrenOf.has(pid)) {
                childrenOf.set(pid, []);
            }
            childrenOf.get(pid)!.push(k);
        }

        const excluded = new Set<number>();
        if (editingId) {
            const stack = [editingId];
            while (stack.length) {
                const cur = stack.pop()!;
                excluded.add(cur);
                for (const child of childrenOf.get(cur) ?? []) {
                    stack.push(child.id);
                }
            }
        }

        const roots = (childrenOf.get(null) ?? [])
            .slice()
            .sort((a, b) => String(a.nama).localeCompare(String(b.nama)));

        const groups: { label: string; options: SelectOption[] }[] = [];
        for (const root of roots) {
            const options: SelectOption[] = [];
            const walk = (node: KelasListRow, depth: number) => {
                if (excluded.has(node.id)) {
                    return;
                }
                options.push({
                    value: node.id,
                    label:
                        depth > 0
                            ? `${'  '.repeat(depth)}${node.nama}`
                            : node.nama,
                });
                for (const child of childrenOf.get(node.id) ?? []) {
                    walk(child, depth + 1);
                }
            };
            walk(root, 0);
            if (options.length) {
                groups.push({ label: root.nama, options });
            }
        }

        return groups;
    });

    function nodeMatches(node: KelasTreeNode): boolean {
        const q = query.trim().toLowerCase();
        return (
            String(node.nama ?? '').toLowerCase().includes(q) ||
            String(node.jurusan?.name ?? '').toLowerCase().includes(q) ||
            String(node.walikelas?.nama_lengkap ?? '').toLowerCase().includes(q)
        );
    }

    const filteredTree = $derived.by(() => {
        if (!hasQuery) {
            return kelas_parent;
        }
        const filter = (nodes: KelasTreeNode[]): KelasTreeNode[] =>
            nodes
                .map((node) => {
                    const children = filter(node.children ?? []);
                    if (!nodeMatches(node) && !children.length) {
                        return null;
                    }
                    return {
                        ...node,
                        children: nodeMatches(node)
                            ? (node.children ?? [])
                            : children,
                    };
                })
                .filter((n): n is KelasTreeNode => n !== null);
        return filter(kelas_parent);
    });

    const matchCount = $derived.by(() => {
        if (!hasQuery) {
            return 0;
        }
        let count = 0;
        const walk = (nodes: KelasTreeNode[]) => {
            for (const node of nodes) {
                if (nodeMatches(node)) {
                    count++;
                }
                walk(node.children ?? []);
            }
        };
        walk(kelas_parent);
        return count;
    });

    const totalKelas = $derived((kelas_list ?? []).length);

    function totalSiswa(node: Record<string, any>): number {
        let total = node.siswa_count ?? 0;
        for (const child of node.children ?? []) {
            total += totalSiswa(child);
        }
        return total;
    }

    function isCollapsed(id: number): boolean {
        return !hasQuery && collapsedMap[id] === true;
    }

    function toggleCollapsed(id: number) {
        collapsedMap = { ...collapsedMap, [id]: !collapsedMap[id] };
    }

    let namaTouched = $state(false);

    const form = useForm({
        nama: '',
        deskripsi: '',
        jurusan_id: null as number | null,
        guru_id: null as number | null,
        parent_id: null as number | null,
        active: false,
    });

    function getDepth(id: number | null): number {
        const list = kelas_list ?? [];
        let depth = 0;
        let cur = id;
        while (cur != null) {
            const node = list.find((k) => k.id === cur);
            if (!node) break;
            cur = (node.parent_id ?? null) as number | null;
            depth++;
        }
        return Math.max(0, depth - 1);
    }

    function nextSiblingNumber(): number {
        const list = kelas_list ?? [];
        const siblings = list.filter(
            (k) => (k.parent_id ?? null) === form.parent_id && k.id !== editingId,
        );
        let maxNum = 0;
        let numbered = 0;
        for (const s of siblings) {
            const m = String(s.nama ?? '').match(/[- ](\d+)$/);
            if (m) {
                numbered++;
                maxNum = Math.max(maxNum, Number(m[1]));
            }
        }
        return maxNum > 0 ? maxNum + 1 : numbered + 1;
    }

    function rebuildNama() {
        if (namaTouched) {
            return;
        }
        const list = kelas_list ?? [];
        const parent = list.find((k) => k.id === form.parent_id);
        if (!parent) {
            return;
        }
        const jurusan = (jurusans ?? []).find((j) => j.id === form.jurusan_id);

        if (getDepth(parent.id) === 0) {
            const code = jurusan?.kode || jurusan?.name || '';
            form.nama = [parent.nama, code].filter(Boolean).join('-');
            return;
        }

        if (editingId) {
            const self = (kelas_list ?? []).find((k) => k.id === editingId);
            const m = String(self?.nama ?? '').match(/-(\d+)$/);
            if (m && (self?.parent_id ?? null) === form.parent_id) {
                form.nama = `${parent.nama}-${m[1]}`;
                return;
            }
        }

        form.nama = `${parent.nama}-${nextSiblingNumber()}`;
    }

    function openCreate() {
        editingId = null;
        namaTouched = false;
        form.reset();
        form.clearErrors();
        form.active = false;
        form.jurusan_id = null;
        form.guru_id = null;
        form.parent_id = null;
        modalOpen = true;
    }

    function openEdit(node: KelasTreeNode) {
        editingId = node.id;
        namaTouched = false;
        form.reset();
        form.clearErrors();
        form.nama = node.nama ?? '';
        form.deskripsi = node.deskripsi ?? '';
        form.jurusan_id = node.jurusan?.id ?? null;
        form.guru_id = node.walikelas?.id ?? null;
        form.parent_id = node.parent_id ?? null;
        form.active = !!node.active;
        modalOpen = true;
    }

    function onParentChange(value: string) {
        form.parent_id = value === '' ? null : Number(value);
        const parent = (kelas_list ?? []).find((k) => k.id === form.parent_id);
        form.jurusan_id = parent?.jurusan_id ?? null;
        rebuildNama();
    }

    function onJurusanChange(value: unknown) {
        form.jurusan_id = extractId(value);
        rebuildNama();
    }

    function onGuruChange(value: unknown) {
        form.guru_id = extractId(value);
    }

    function onNamaInput() {
        namaTouched = true;
    }

    function submit() {
        if (editingId) {
            const route = KelasController.update({ kelas: editingId });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: () => (modalOpen = false) },
            );
        } else {
            const route = KelasController.store();
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: () => (modalOpen = false) },
            );
        }
    }

    function countDescendants(node: KelasTreeNode): number {
        if (!node.children || !node.children.length) {
            return 0;
        }
        let total = 0;
        for (const child of node.children) {
            total += 1 + countDescendants(child);
        }
        return total;
    }

    async function confirmDelete(node: KelasTreeNode) {
        const count = countDescendants(node);
        const siswa = totalSiswa(node);
        const parts = [`Hapus kelas "${node.nama}"?`];
        if (count > 0) {
            parts.push(`Kelas beserta ${count} kelas terkait akan ikut terhapus.`);
        }
        if (siswa > 0) {
            parts.push(
                `Kelas ini memiliki ${siswa} siswa — penghapusan akan ditolak.`,
            );
        }
        const ok = await confirm.show({
            title: 'Hapus Kelas',
            message: parts.join('\n'),
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) {
            return;
        }
        router.delete(KelasController.destroy({ kelas: node.id }).url);
    }
</script>

{#snippet node(item: KelasTreeNode, depth: number)}
    <li>
        <div
            class="d-flex flex-wrap align-items-center gap-2 px-2 py-2 rounded kelas-row"
        >
            {#if item.children && item.children.length}
                <button
                    type="button"
                    class="btn btn-sm btn-link p-0 text-secondary kelas-toggle"
                    class:is-open={!isCollapsed(item.id)}
                    onclick={() => toggleCollapsed(item.id)}
                    title={isCollapsed(item.id) ? 'Perluas' : 'Ciutkan'}
                >
                    <i class="bi bi-chevron-right"></i>
                </button>
            {:else}
                <span class="kelas-toggle kelas-toggle--empty"></span>
            {/if}
            <i
                class={`bi ${item.children && item.children.length ? 'bi-folder2' : 'bi-mortarboard-fill'} text-secondary`}
            ></i>
            <span class="fs-6 fw-bold text-body text-nowrap">{item.nama}</span>
            {#if depth === 0}
                <span class="badge text-bg-secondary text-xs fw-light"
                    >Tingkat</span
                >
            {:else if depth === 1}
                <span class="badge text-bg-info text-xs fw-light">Jurusan</span>
            {:else}
                <span class="badge text-bg-primary text-xs fw-light">Kelas</span>
            {/if}
            {#if item.active}
                <span class="badge text-bg-success text-xs fw-light">Aktif</span>
            {:else}
                <span class="badge text-bg-secondary text-xs fw-light"
                    >Nonaktif</span
                >
            {/if}
            <span
                class="badge text-bg-light border text-xs fw-light text-nowrap"
                title="Jumlah siswa"
            >
                <i class="bi bi-people-fill"></i> {totalSiswa(item)} siswa
            </span>
            <span
                class="d-flex flex-wrap text-xs align-items-center gap-2 text-secondary small"
            >
                {#if item.jurusan}<span
                        ><i class="bi bi-bookmark-fill"></i>
                        {item.jurusan.name}</span
                    >{/if}
                {#if item.walikelas}<span
                        ><i class="bi bi-person-fill"></i>
                        {item.walikelas.nama_lengkap}</span
                    >{/if}
            </span>
            <span class="ms-auto d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    onclick={() => openEdit(item)}
                    title="Edit"
                >
                    <i class="bi bi-pencil"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick={() => confirmDelete(item)}
                    title="Hapus"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </span>
        </div>
        {#if item.children && item.children.length && !isCollapsed(item.id)}
            <ul class="list-unstyled ps-4">
                {#each item.children as child (child.id)}
                    {@render node(child, depth + 1)}
                {/each}
            </ul>
        {/if}
    </li>
{/snippet}

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-semibold mb-1">Kelas</h1>
            <span class="text-secondary small">
                {totalKelas} kelas · {kelas_parent.length} tingkat
            </span>
        </div>
        <Button color="primary" size="sm" onclick={openCreate}>
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </Button>
    </div>

    <div class="input-group mb-3 kelas-search">
        <span class="input-group-text" style="background-color: var(--app-shell-surface);">
            <i class="bi bi-search"></i>
        </span>
        <input
            type="search"
            class="form-control"
            placeholder="Cari nama kelas, jurusan, atau wali kelas…"
            bind:value={query}
        />
        {#if hasQuery}
            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick={() => (query = '')}
                title="Hapus pencarian"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        {/if}
    </div>

    {#if filteredTree.length}
        <ul class="list-unstyled border rounded p-2 mb-0" style="background-color: var(--app-shell-surface);">
            {#each filteredTree as parent (parent.id)}
                {@render node(parent, 0)}
            {/each}
        </ul>
        {#if hasQuery}
            <div class="text-secondary small mt-2">
                {matchCount} hasil ditemukan untuk
                "<strong>{query.trim()}</strong>"
            </div>
        {/if}
    {:else if hasQuery}
        <EmptyState
            icon="bi-search"
            message={`Tidak ada kelas yang cocok dengan pencarian "${query.trim()}".`}
            variant="card"
        />
    {:else}
        <EmptyState
            icon="bi-inbox"
            message="Belum ada data kelas."
            hint="Mulai dengan membuat kelas Tingkat (X, XI, XII)."
            variant="card"
        />
    {/if}
</div>

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editingId ? 'Edit Kelas' : 'Tambah Kelas'}
    </ModalHeader>
    <ModalBody>
        <FormGroup>
            <Label for="parent_id">Kelas Induk</Label>
            <select
                id="parent_id"
                class="form-select"
                value={form.parent_id ?? ''}
                onchange={(e) =>
                    onParentChange(
                        (e.currentTarget as HTMLSelectElement).value,
                    )}
            >
                <option value="">Tidak ada (kelas induk)</option>
                {#each parentGroups as group (group.label)}
                    <optgroup label={group.label}>
                        {#each group.options as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </optgroup>
                {/each}
            </select>
            {#if form.errors.parent_id}
                <small class="text-danger">{form.errors.parent_id}</small>
            {/if}
            <small class="text-secondary d-block mt-1">
                Struktur: <code>Tingkat</code> → <code>Tingkat-Jurusan</code> →
                <code>Tingkat-Jurusan-Nomor</code>.
            </small>
        </FormGroup>

        <FormGroup>
            <Label for="nama">Nama Kelas</Label>
            <Input
                id="nama"
                bind:value={form.nama}
                oninput={onNamaInput}
                invalid={!!form.errors.nama}
            />
            {#if form.errors.nama}
                <small class="text-danger">{form.errors.nama}</small>
            {/if}
            <small class="text-secondary d-block mt-1">
                Nama otomatis mengikuti struktur induk di atas.
            </small>
        </FormGroup>

        <FormGroup>
            <Label for="jurusan_id">Jurusan</Label>
            <Select
                items={jurusanOptions}
                value={form.jurusan_id}
                placeholder="Pilih jurusan…"
                getOptionValue={(item) => item.value}
                onchange={onJurusanChange}
            />
            {#if form.errors.jurusan_id}
                <small class="text-danger">{form.errors.jurusan_id}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="guru_id">Wali Kelas</Label>
            <Select
                items={guruOptions}
                value={form.guru_id}
                placeholder="Pilih wali kelas…"
                getOptionValue={(item) => item.value}
                onchange={onGuruChange}
            />
            {#if form.errors.guru_id}
                <small class="text-danger">{form.errors.guru_id}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="deskripsi">Deskripsi</Label>
            <Input id="deskripsi" type="textarea" bind:value={form.deskripsi} />
        </FormGroup>

        <FormGroup check>
            <ToggleSwitch
                checked={form.active}
                label="Aktif"
                onchange={(v) => (form.active = v)}
            />
        </FormGroup>
    </ModalBody>
    <ModalFooter>
        <Button
            color="secondary"
            outline
            onclick={() => (modalOpen = !modalOpen)}>Batal</Button
        >
        <Button color="primary" onclick={submit} disabled={form.processing}>
            {editingId ? 'Simpan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>

<style>
    .kelas-search {
        max-width: 420px;
    }

    .kelas-toggle {
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.15s ease;
    }

    .kelas-toggle.is-open {
        transform: rotate(90deg);
    }

    .kelas-toggle--empty {
        visibility: hidden;
    }

    .kelas-row:hover {
        background: var(--app-shell-primary-soft);
    }
</style>