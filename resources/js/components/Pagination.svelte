<script lang="ts">
    import type { PaginationMeta } from '@/types/models';

    let {
        meta,
        onPageChange,
    }: {
        meta: PaginationMeta;
        onPageChange: (page: number) => void;
    } = $props();

    const current = $derived(meta.current_page);
    const last = $derived(meta.last_page);

    const pages = $derived.by(() => {
        const result: (number | '...')[] = [];
        const c = current;
        const l = last;

        if (l <= 7) {
            for (let i = 1; i <= l; i++) result.push(i);
            return result;
        }

        result.push(1);
        const start = Math.max(2, c - 1);
        const end = Math.min(l - 1, c + 1);

        if (start > 2) result.push('...');
        for (let i = start; i <= end; i++) result.push(i);
        if (end < l - 1) result.push('...');
        result.push(l);

        return result;
    });

    function go(page: number) {
        if (page < 1 || page > last || page === current) return;
        onPageChange(page);
    }
</script>

<div
    class="crud-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"
>
    <div class="crud-pagination-info small text-muted">
        {#if meta.total > 0}
            Menampilkan {meta.from ?? 0}&ndash;{meta.to ?? 0} dari {meta.total} data
        {:else}
            Tidak ada data
        {/if}
    </div>

    <nav aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0 crud-pagination-list">
            <li class="page-item {current <= 1 ? 'disabled' : ''}">
                <button
                    class="page-link"
                    aria-label="Sebelumnya"
                    onclick={() => go(current - 1)}
                >
                    <i class="bi bi-chevron-left"></i>
                </button>
            </li>

            {#each pages as p (typeof p === 'number' ? `p-${p}` : 'ellipsis')}
                {#if p === '...'}
                    <li class="page-item disabled">
                        <span class="page-link">&hellip;</span>
                    </li>
                {:else}
                    <li class="page-item {p === current ? 'active' : ''}">
                        <button class="page-link" onclick={() => go(p)}>
                            {p}
                        </button>
                    </li>
                {/if}
            {/each}

            <li class="page-item {current >= last ? 'disabled' : ''}">
                <button
                    class="page-link"
                    aria-label="Berikutnya"
                    onclick={() => go(current + 1)}
                >
                    <i class="bi bi-chevron-right"></i>
                </button>
            </li>
        </ul>
    </nav>
</div>

<style>
    .crud-pagination {
        padding: 0.75rem 1rem;
        border-top: 1px solid var(--inv-gray-100);
        background: var(--inv-white);
    }

    .crud-pagination-list .page-link {
        border-radius: 8px;
        margin: 0 2px;
        color: var(--inv-gray-700);
        border-color: var(--inv-gray-100);
        min-width: 34px;
        text-align: center;
    }

    .crud-pagination-list .page-link:hover {
        background: var(--inv-gray-50);
    }

    .crud-pagination-list .page-item.active .page-link {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: var(--inv-white);
    }

    .crud-pagination-list .page-item.active .page-link:hover {
        background: var(--bs-primary);
    }

    .crud-pagination-list .page-item.disabled .page-link {
        color: var(--inv-gray-500);
    }
</style>
