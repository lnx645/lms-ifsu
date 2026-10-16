<script lang="ts">
    // Renderer: Matrix
    let {
        detail,
        mode = 'text',
        jawaban = $bindable([] as string[][]),
        disabled = false,
    }: {
        detail: { rows: string[]; cols: string[] };
        mode?: 'text' | 'checkbox';
        jawaban?: string[][];
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.rows ?? []).length > 0) {
            initialized = true;
            const curr = jawaban ?? [];
            if (curr.length === 0) {
                jawaban = (safe.rows ?? []).map(() => (safe.cols ?? []).map(() => ''));
            }
        }
    });
    const getVal = (i: number, j: number): string => (jawaban ?? [])[i]?.[j] ?? '';
    const setVal = (i: number, j: number, v: string) => {
        const arr: string[][] = [...(jawaban ?? [])];
        while (arr.length <= i) arr.push([]);
        arr[i] = [...(arr[i] ?? [])];
        while (arr[i].length <= j) arr[i].push('');
        arr[i][j] = v;
        jawaban = arr;
    };
</script>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead><tr><th></th>{#each (safe.cols ?? []) as c}<th class="text-center">{@html c}</th>{/each}</tr></thead>
        <tbody>
            {#each (safe.rows ?? []) as r, i}
                <tr>
                    <th>{@html r}</th>
                    {#each (safe.cols ?? []) as _, j}
                        <td class="text-center">
                            {#if mode === 'text'}
                                <input class="form-control form-control-sm" value={getVal(i, j)} oninput={(e) => setVal(i, j, (e.target as HTMLInputElement).value)} disabled={disabled} />
                            {:else}
                                <input type="checkbox" class="form-check-input"
                                    checked={getVal(i, j) === '1'}
                                    onchange={(e) => { if (!disabled) setVal(i, j, (e.target as HTMLInputElement).checked ? '1' : ''); }} />
                            {/if}
                        </td>
                    {/each}
                </tr>
            {/each}
        </tbody>
    </table>
</div>
