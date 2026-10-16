<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    let {
        rows = $bindable(),
        cols = $bindable(),
        kunci = $bindable(),
        mode = 'text',
    }: {
        rows: string[]; cols: string[]; kunci: string[][];
        mode?: 'text' | 'checkbox';
    } = $props();

    function tambahRow() {
        rows = [...rows, ''];
        kunci = [...kunci, new Array(cols.length).fill('')];
    }
    function tambahCol() {
        cols = [...cols, ''];
        kunci = kunci.map(r => [...r, '']);
    }
</script>

<Card class="mb-3"><CardBody>
    <div class="fw-semibold mb-2">Matrix {mode === 'text' ? '(input text)' : '(checkbox)'}</div>
    <div class="table-responsive"><table class="table table-bordered mb-2">
        <thead><tr><th style="min-width:120px"></th>
            {#each cols as _, j}<th><input class="form-control form-control-sm" bind:value={cols[j]} placeholder="Kolom {j + 1}" /></th>{/each}
        </tr></thead>
        <tbody>{#each rows as _, i}<tr>
            <th><input class="form-control form-control-sm" bind:value={rows[i]} placeholder="Baris {i + 1}" /></th>
            {#each cols as _, j}<td class="text-center">
                {#if mode === 'text'}
                    <input class="form-control form-control-sm" bind:value={kunci[i][j]} placeholder="Jawaban" />
                {:else}
                    <input type="checkbox" class="form-check-input" checked={kunci[i][j] === '1'}
                        onchange={(e) => { kunci[i][j] = (e.target as HTMLInputElement).checked ? '1' : ''; }} />
                {/if}
            </td>{/each}
        </tr>{/each}</tbody>
    </table></div>
    <div class="d-flex gap-2">
        <Button color="outline-secondary" size="sm" onclick={tambahRow}>+ Baris</Button>
        <Button color="outline-secondary" size="sm" onclick={tambahCol}>+ Kolom</Button>
    </div>
</CardBody></Card>
