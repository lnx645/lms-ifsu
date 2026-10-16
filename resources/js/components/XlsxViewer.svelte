<script lang="ts">
    import * as XLSX from 'xlsx';
    import { Spinner } from '@sveltestrap/sveltestrap';
    import { onMount } from 'svelte';

    let { url }: { url: string } = $props();

    const MAX_ROWS = 500;

    let loading = $state(true);
    let errorMessage = $state('');
    let sheets: { name: string; rows: string[][] }[] = [];
    let activeSheet = $state(0);

    onMount(async () => {
        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error('fetch failed');
            }
            const data = await response.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });

            sheets = workbook.SheetNames.map((name) => {
                const worksheet = workbook.Sheets[name];
                const rawRows = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1,
                    raw: false,
                    defval: '',
                }) as unknown[][];

                const rows = rawRows
                    .map((row) => row.map((cell) => (cell === null || cell === undefined ? '' : String(cell))))
                    .filter((row) => row.some((cell) => cell.trim() !== ''));

                return { name, rows };
            });

            activeSheet = 0;
        } catch (error) {
            errorMessage = 'Gagal memuat berkas Excel.';
        } finally {
            loading = false;
        }
    });

    const visibleRows = $derived(sheets[activeSheet]?.rows ?? []);
    const isTruncated = $derived(visibleRows.length > MAX_ROWS + 1);
</script>

<div class="xlsx-viewer">
    {#if loading}
        <div class="text-center text-secondary py-5">
            <Spinner color="primary" />
            <p class="mt-2 mb-0 small">Memuat berkas Excel…</p>
        </div>
    {:else if errorMessage}
        <div class="alert alert-danger border rounded-1 m-3 mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>{errorMessage}
        </div>
    {:else if sheets.length === 0}
        <div class="text-center text-secondary py-5">
            <i class="bi bi-table" style="font-size: 2rem"></i>
            <p class="mt-2 mb-0 small">Berkas Excel kosong.</p>
        </div>
    {:else}
        {#if sheets.length > 1}
            <div class="xlsx-viewer__tabs">
                {#each sheets as sheet, i (sheet.name)}
                    <button
                        type="button"
                        class="xlsx-viewer__tab {i === activeSheet ? 'is-active' : ''}"
                        onclick={() => (activeSheet = i)}
                    >
                        <i class="bi bi-table me-1"></i>{sheet.name}
                    </button>
                {/each}
            </div>
        {/if}

        <div class="table-responsive xlsx-viewer__table-wrap">
            <table class="table table-sm table-bordered table-striped mb-0">
                {#if visibleRows.length}
                    <thead>
                        <tr>
                            {#each visibleRows[0] as cell}
                                <th class="text-nowrap">{cell}</th>
                            {/each}
                        </tr>
                    </thead>
                    <tbody>
                        {#each visibleRows.slice(1, MAX_ROWS + 1) as row, rowIndex (rowIndex)}
                            <tr>
                                {#each row as cell}
                                    <td class="text-nowrap">{cell}</td>
                                {/each}
                            </tr>
                        {/each}
                    </tbody>
                {/if}
            </table>
        </div>

        {#if isTruncated}
            <div class="text-muted small px-3 py-2">
                Hanya menampilkan {MAX_ROWS} baris pertama.
            </div>
        {/if}
    {/if}
</div>

<style>
    .xlsx-viewer {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        overflow: hidden;
        background: var(--bs-body-bg);
    }

    .xlsx-viewer__tabs {
        display: flex;
        gap: 0.25rem;
        padding: 0.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        overflow-x: auto;
    }

    .xlsx-viewer__tab {
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        padding: 0.3rem 0.75rem;
        border: 1px solid transparent;
        border-radius: var(--bs-border-radius-sm);
        background: transparent;
        color: var(--bs-body-color);
        font-size: var(--bs-font-size-sm);
        font-weight: 600;
        cursor: pointer;
    }

    .xlsx-viewer__tab:hover {
        background: var(--bs-secondary-bg);
    }

    .xlsx-viewer__tab.is-active {
        background: var(--bs-primary);
        color: #fff;
    }

    .xlsx-viewer__table-wrap {
        max-height: 70vh;
    }

    .xlsx-viewer :global(table) {
        min-width: 100%;
    }
</style>