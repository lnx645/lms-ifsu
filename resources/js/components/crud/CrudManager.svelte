<script lang="ts">
    import type { Component, Snippet } from 'svelte';
    import { useForm, router, usePage } from '@inertiajs/svelte';
    import {
        Button,
        Card,
        CardBody,
        Table,
        Modal,
        ModalHeader,
        ModalBody,
        ModalFooter,
        FormGroup,
        Input,
        Label,
        Badge,
    } from '@sveltestrap/sveltestrap';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import Select from '@/components/Select.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import ToggleSwitch from '@/components/ToggleSwitch.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import type { RouteDefinition } from '@/wayfinder';

    type CrudItem = Record<string, unknown> & { id: number | string };

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

    export type CrudFieldOption = { value: string | number; label: string };

    export type CrudFilter = {
        name: string;
        label: string;
        type?: 'text' | 'select' | 'date';
        placeholder?: string;
        options?: CrudFieldOption[];
        size?: 'xs' | 'sm' | 'lg';
    };

    export type CrudPagination = {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        from?: number | null;
        to?: number | null;
    };

    export type CrudAction = {
        key: string;
        label?: string;
        icon?: string;
        color?: string;
        class?: string;
        size?: 'sm' | 'lg';
        onClick?: (item?: CrudItem) => void;
    };

    export type CrudField = {
        name: string;
        label: string;
        type?:
            | 'text'
            | 'email'
            | 'number'
            | 'textarea'
            | 'time'
            | 'select'
            | 'date'
            | 'file'
            | 'image'
            | 'checkbox';
        placeholder?: string;
        options?: CrudFieldOption[];
        accept?: string;
        required?: boolean;
        editable?: boolean;
        multiple?: boolean;
        size?: 'xs' | 'sm' | 'lg';
    };

    type AnyRoute = RouteDefinition<'get' | 'post' | 'put' | 'delete'>;

    type CrudController = {
        store: (options?: any) => AnyRoute;
        update: (args: any) => AnyRoute;
        destroy: (args: any) => AnyRoute;
    };

    let {
        title = '',
        subtitle = '',
        columns = [],
        fields = [],
        items = [],
        controller,
        resourceName,
        createLabel = 'Tambah',
        emptyText = 'Belum ada data.',
        actions = [],
        toolbarActions = [],
        searchable = false,
        searchPlaceholder = 'Cari…',
        filters = [],
        query = {},
        only = [],
        onQuery,
        onCreateSuccess,
        pagination,
    }: {
        title?: string;
        subtitle?: string;
        columns?: CrudColumn[];
        fields?: CrudField[];
        items?: CrudItem[];
        controller: CrudController;
        resourceName?: string;
        createLabel?: string;
        emptyText?: string;
        actions?: CrudAction[];
        toolbarActions?: CrudAction[];
        searchable?: boolean;
        searchPlaceholder?: string;
        filters?: CrudFilter[];
        query?: Record<string, string>;
        only?: string[];
        onQuery?: (params: Record<string, string>) => void;
        onCreateSuccess?: (created?: CrudItem) => void;
        pagination?: CrudPagination;
    } = $props();

    const label = $derived(resourceName ?? title ?? 'data');

    let modalOpen = $state(false);
    let editing = $state<CrudItem | null>(null);
    let previews = $state<Record<string, string>>({});

    const form = useForm(
        Object.fromEntries(
            fields.map((f) => [
                f.name,
                f.type === 'checkbox' ? false : f.multiple ? [] : '',
            ]),
        ),
    );

    function display(col: CrudColumn, item: CrudItem): string {
        const value = item[col.key];
        return col.format ? col.format(value, item) : String(value ?? '');
    }

    function fieldValue<T = unknown>(name: string): T {
        return (form as Record<string, unknown>)[name] as T;
    }

    function setFile(name: string, file: File | null) {
        (form as Record<string, unknown>)[name] = file;
        if (previews[name]) {
            URL.revokeObjectURL(previews[name]);
        }
        previews[name] = file ? URL.createObjectURL(file) : '';
    }

    function openCreate() {
        editing = null;
        form.reset();
        previews = {};
        modalOpen = true;
    }

    function openEdit(item: CrudItem) {
        editing = item;
        form.reset();
        previews = {};
        for (const field of fields) {
            (form as Record<string, unknown>)[field.name] =
                field.type === 'checkbox'
                    ? Boolean(item[field.name])
                    : (item[field.name] ?? '');
        }
        modalOpen = true;
    }

    function submit() {
        const onSuccess = () => {
            modalOpen = false;
            form.reset();
        };

        if (editing) {
            const route = controller.update(editing);
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess },
            );
        } else {
            const route = controller.store();
            const created = { ...(form as Record<string, unknown>) } as CrudItem;
            form.submit(
                { url: route.url, method: route.method },
                {
                    onSuccess: () => {
                        modalOpen = false;
                        form.reset();
                        onCreateSuccess?.(created);
                    },
                },
            );
        }
    }

    async function confirmDelete(item: CrudItem) {
        const ok = await confirm.show({
            title: `Hapus ${label}?`,
            message: `${String(item.name ?? item.id)} akan dihapus secara permanen.`,
        });

        if (!ok) {
            return;
        }

        router.delete(controller.destroy(item).url);
    }

    const page = usePage();

    let search = $state(query.search ?? '');
    let filterValues = $state<Record<string, string>>(
        Object.fromEntries(filters.map((f) => [f.name, query[f.name] ?? ''])),
    );

    let hasActive = $derived(
        search.trim() !== '' ||
            filters.some((f) => (filterValues[f.name] ?? '') !== ''),
    );

    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function onSearchInput() {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(() => applyQuery(), 350);
    }

    function optionValue(value: unknown): string {
        if (
            value &&
            typeof value === 'object' &&
            'value' in (value as Record<string, unknown>)
        ) {
            return String((value as Record<string, unknown>).value);
        }
        return value == null ? '' : String(value);
    }

    function setFilter(name: string, value: unknown) {
        filterValues[name] = optionValue(value);
        applyQuery();
    }

    function buildParams(): Record<string, string> {
        const params: Record<string, string> = {};
        if (search.trim()) params.search = search.trim();
        for (const f of filters) {
            const value = filterValues[f.name] ?? '';
            if (value !== '') params[f.name] = value;
        }
        return params;
    }

    function navigate(params: Record<string, string>) {
        if (onQuery) {
            onQuery(params);
            return;
        }
        const url = (page.url as string).split('?')[0];
        router.get(url, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            ...(only.length ? { only } : {}),
        });
    }

    function applyQuery() {
        navigate(buildParams());
    }

    function goToPage(page: number) {
        if (!pagination) return;
        if (
            page < 1 ||
            page > pagination.last_page ||
            page === pagination.current_page
        ) {
            return;
        }
        const params = buildParams();
        params.page = String(page);
        navigate(params);
    }

    function resetFilters() {
        search = '';
        for (const f of filters) filterValues[f.name] = '';
        applyQuery();
    }
</script>

<AppHead {title} />

<PageHeader {title} {subtitle}>
    {#snippet actions()}
        {#each toolbarActions as act (act.key)}
            <Button
                color={act.color ?? 'light'}
                size={act.size ?? "sm"}
                class={act.class}
                title={act.label}
                onclick={() => act.onClick?.()}
            >
                {#if act.icon}
                    <i class={act.icon}></i>
                {/if}
                {#if act.label}
                    {act.label}
                {/if}
            </Button>
        {/each}
        <Button size="sm" color="primary" onclick={openCreate}>
            <i class="bi bi-plus-lg"></i>
            {createLabel}
        </Button>
    {/snippet}
</PageHeader>

{#if searchable || filters.length > 0}
    <div class="crud-filterbar d-flex flex-wrap align-items-center gap-2 mb-3">
        {#if searchable}
            <div class="input-group input-group-sm crud-search">
                <span class="input-group-text" style="background-color: var(--app-shell-surface);">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    class="form-control"
                    type="search"
                    placeholder={searchPlaceholder}
                    bind:value={search}
                    oninput={onSearchInput}
                />
            </div>
        {/if}

        {#each filters as f (f.name)}
            <div class="input-group input-group-sm crud-filter-input">
                <span class="input-group-text">{f.label}</span>
                {#if f.type === 'select'}
                    <select
                        class="form-select"
                        value={filterValues[f.name]}
                        onchange={(e: Event) =>
                            setFilter(
                                f.name,
                                (e.currentTarget as HTMLSelectElement).value,
                            )}
                    >
                        <option value="">{f.placeholder ?? 'Semua'}</option>
                        {#each f.options ?? [] as opt (String(opt.value))}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                {:else}
                    <input
                        class="form-control"
                        type={f.type ?? 'text'}
                        placeholder={f.placeholder ?? f.label}
                        bind:value={filterValues[f.name]}
                        oninput={() => applyQuery()}
                    />
                {/if}
            </div>
        {/each}

        {#if hasActive}
            <Button
                size="sm"
                color="light"
                class="crud-reset"
                onclick={resetFilters}
            >
                <i class="bi bi-x-lg me-1"></i> Reset
            </Button>
        {/if}
    </div>
{/if}

<Card class="border rounded-1 shadow-sm overflow-hidden">
    <CardBody class="p-0  table-responsive">
        <table class="crud-table mb-0 align-middle">
            <thead class="crud-thead">
                <tr>
                    {#each columns as col (col.key)}
                        <th class={col.center ? 'text-center' : ''}
                            >{col.label}</th
                        >
                    {/each}
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {#each items as item (item.id)}
                    <tr>
                        {#each columns as col (col.key)}
                            <td class={col.center ? 'text-center' : ''}>
                                {#if col.cell}
                                    {@render col.cell(item)}
                                {:else if col.cellComponent}
                                    <col.cellComponent {item} />
                                {:else if col.badge}
                                    <Badge color={col.badgeColor ?? 'info'}>
                                        {display(col, item)}
                                    </Badge>
                                {:else}
                                    <span class="crud-clamp"
                                        >{display(col, item)}</span
                                    >
                                {/if}
                            </td>
                        {/each}
                        <td class="text-end pe-3">
                            <div
                                class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end"
                            >
                                {#each actions as act (act.key)}
                                    <Button
                                        size={act.size ?? 'sm'}
                                        color={act.color ?? 'light'}
                                        class={act.class}
                                        title={act.label}
                                        onclick={() => act.onClick?.(item)}
                                    >
                                        {#if act.icon}
                                            <i class={act.icon}></i>
                                        {/if}
                                        {#if act.label}
                                            {act.label}
                                        {/if}
                                    </Button>
                                {/each}
                                <Button
                                    size="sm"
                                    color="outline-secondary"
                                    title="Edit"
                                    onclick={() => openEdit(item)}
                                >
                                    <i class="bi bi-pencil"></i>
                                </Button>
                                <Button
                                    size="sm"
                                    color="outline-danger"
                                    title="Hapus"
                                    onclick={() => confirmDelete(item)}
                                >
                                    <i class="bi bi-trash"></i>
                                </Button>
                            </div>
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td
                            colspan={columns.length + 1}
                            class="text-center text-muted py-4"
                        >
                            <i class="bi bi-inbox me-1"></i>
                            {emptyText}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
        {#if pagination}
            <Pagination meta={pagination} onPageChange={goToPage} />
        {/if}
    </CardBody>
</Card>

<Modal
    backdrop="static"
    isOpen={modalOpen}
    toggle={() => (modalOpen = !modalOpen)}
>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editing ? `Edit ${label}` : `Tambah ${label}`}
    </ModalHeader>
    <form
        onsubmit={(e) => {
            e.preventDefault();
            submit();
        }}
    >
        <ModalBody>
            {#each fields as field, i (field.name)}
                <FormGroup class={i === fields.length - 1 ? 'mb-0' : ''}>
                    {#if field.type !== 'checkbox'}
                        <Label for={field.name} class="small fs-6">
                            {field.label}
                        </Label>
                    {/if}

                    {@const locked = !!editing && field.editable === false}

                    {#if field.type === 'select'}
                        <Select
                            items={field.options ?? []}
                            value={(form as Record<string, unknown>)[
                                field.name
                            ]}
                            multiple={field.multiple ?? false}
                            size={field.size}
                            disabled={locked}
                            hasError={!!(form.errors as Record<string, string>)[
                                field.name
                            ]}
                            placeholder={field.placeholder ?? 'Pilih…'}
                            getOptionValue={(item: unknown) =>
                                (item as CrudFieldOption).value}
                            onchange={(v: unknown) =>
                                ((form as Record<string, unknown>)[
                                    field.name
                                ] = Array.isArray(v)
                                    ? v.map(optionValue)
                                    : optionValue(v))}
                        />
                    {:else if field.type === 'textarea'}
                        <Input
                            bsSize="sm"
                            id={field.name}
                            type="textarea"
                            bind:value={
                                (form as Record<string, unknown>)[field.name]
                            }
                            disabled={locked}
                            invalid={!!(form.errors as Record<string, string>)[
                                field.name
                            ]}
                            placeholder={field.placeholder ?? ''}
                        />
                    {:else if field.type === 'checkbox'}
                        <ToggleSwitch
                            checked={Boolean(
                                (form as Record<string, unknown>)[field.name],
                            )}
                            label={field.label}
                            disabled={locked}
                            onchange={(v) =>
                                ((form as Record<string, unknown>)[
                                    field.name
                                ] = v)}
                        />
                    {:else if field.type === 'file' || field.type === 'image'}
                        <Input
                            id={field.name}
                            bsSize="sm"
                            type="file"
                            accept={field.accept ??
                                (field.type === 'image'
                                    ? 'image/*'
                                    : undefined)}
                            disabled={locked}
                            onchange={(e: Event) =>
                                setFile(
                                    field.name,
                                    (e.currentTarget as HTMLInputElement)
                                        .files?.[0] ?? null,
                                )}
                            invalid={!!(form.errors as Record<string, string>)[
                                field.name
                            ]}
                        />
                        {#if fieldValue<string>(field.name)}
                            <div class="mt-2">
                                <img
                                    src={fieldValue<File>(field.name)
                                        ? previews[field.name]
                                        : fieldValue<string>(field.name).startsWith(
                                                '/storage/',
                                            )
                                          ? fieldValue<string>(field.name)
                                          : `/storage/${fieldValue<string>(
                                                field.name,
                                            )}`}
                                    alt={field.label}
                                    class="img-thumbnail"
                                    style="max-height: 96px;"
                                />
                            </div>
                        {/if}
                    {:else}
                        <Input
                            id={field.name}
                            bsSize="sm"
                            type={(field.type ?? 'text') as never}
                            bind:value={
                                (form as Record<string, unknown>)[field.name]
                            }
                            disabled={locked}
                            invalid={!!(form.errors as Record<string, string>)[
                                field.name
                            ]}
                            placeholder={field.placeholder ?? ''}
                        />
                    {/if}

                    {#if (form.errors as Record<string, string>)[field.name]}
                        <div class="text-danger small mt-1">
                            {(form.errors as Record<string, string>)[
                                field.name
                            ]}
                        </div>
                    {/if}
                </FormGroup>
            {/each}
        </ModalBody>
        <ModalFooter>
            <Button
                color="secondary"
                type="button"
                size="sm"
                outline
                onclick={() => (modalOpen = false)}
            >
                Batal
            </Button>
            <Button
                color="primary"
                size="sm"
                type="submit"
                disabled={form.processing}
            >
                {editing ? 'Simpan Perubahan' : 'Simpan'}
            </Button>
        </ModalFooter>
    </form>
</Modal>

<style>
    .crud-table {
        width: 100%;
    }
    .crud-table thead th {
        background: var(--app-shell-surface, #f8f9fa);
        font-weight: 600;
        color: var(--app-shell-text-muted, var(--inv-gray-700));
        text-transform: capitalize;
        letter-spacing: 0.03em;
        font-size: 0.75rem;
        border-bottom: 1px solid var(--app-shell-border, #dee2e6);
        padding: 0.4rem 1rem;
    }

    .crud-table tbody td {
        border-bottom: 1px solid var(--app-shell-border, #dee2e6);
        padding: 0.3rem 1rem;
        vertical-align: middle;
        font-size: 0.75rem;
    }

    .crud-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    .crud-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .crud-search {
        flex: 1 1 240px;
        min-width: 0;
        max-width: 360px;
    }

    .crud-filter-input {
        flex: 1 1 180px;
        min-width: 0;
    }

    .crud-filterbar {
        width: 100%;
    }

    @media (max-width: 575.98px) {
        .crud-search,
        .crud-filter-input {
            flex: 1 1 100%;
            max-width: 100%;
        }
    }

    .crud-filterbar .crud-reset {
        color: var(--inv-gray-700);
    }
</style>
