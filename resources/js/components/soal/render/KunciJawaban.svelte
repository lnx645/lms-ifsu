<script lang="ts">
    import Latex from '@/components/Latex.svelte';

    let { tipe, detail }: { tipe: string; detail: Record<string, any> } = $props();

    const d = detail ?? {};
    const huruf = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    // Helper: dapatkan teks opsi PG berdasarkan kunci (a/b/c/d atau index)
    function opsiTeksPG(): string {
        const opsi = (d.opsi ?? []) as { teks: string }[];
        const kunci = d.kunci;
        if (!kunci || opsi.length === 0) return '';
        // kunci bisa 'a', 'b', atau index 0,1,2
        let idx = -1;
        if (typeof kunci === 'string') {
            const k = kunci.toLowerCase().trim();
            if (/^[a-z]$/.test(k)) idx = k.charCodeAt(0) - 97;
            else idx = parseInt(k, 10);
        } else if (typeof kunci === 'number') {
            idx = kunci;
        }
        if (idx >= 0 && idx < opsi.length) {
            return `${huruf[idx] ?? idx + 1}. ${opsi[idx].teks ?? ''}`;
        }
        return String(kunci);
    }
</script>

<div class="mt-2 p-2 bg-success bg-opacity-10 border border-success rounded small">
    <strong><i class="bi bi-key me-1"></i>Kunci Jawaban:</strong>

    {#if tipe === 'pilihan_ganda' || tipe === 'pilihan_ganda_citra'}
        <div class="mt-1"><Latex teks={opsiTeksPG()} /></div>

    {:else if tipe === 'pilihan_ganda_kompleks'}
        <div class="mt-1">
            {#if Array.isArray(d.kunci)}
                {@const opsi = (d.opsi ?? []) as { teks: string }[]}
                {#each d.kunci as k}
                    {@const idx = typeof k === 'string' && /^[a-z]$/i.test(k) ? k.toLowerCase().charCodeAt(0) - 97 : Number(k)}
                    <div class="mb-1">
                        <span class="badge bg-success me-1">{huruf[idx] ?? k}</span>
                        {#if opsi[idx]}<Latex teks={opsi[idx].teks ?? ''} />{/if}
                    </div>
                {/each}
            {:else}
                <span>{String(d.kunci ?? '-')}</span>
            {/if}
        </div>

    {:else if tipe === 'menjodohkan' || tipe === 'menjodohkan_banyak'}
        <div class="mt-1">
            {#each (d.pasangan ?? []) as p, i}
                <div class="d-flex gap-2 align-items-center mb-1">
                    <span class="badge bg-secondary">{i + 1}</span>
                    <span><Latex teks={p.kiri ?? ''} /></span>
                    <i class="bi bi-arrow-right text-success"></i>
                    <span><Latex teks={p.kanan ?? ''} /></span>
                </div>
            {/each}
            {#if (d.pengecoh ?? []).length > 0}
                <div class="mt-1 text-muted">
                    <small>Pengecoh: {(d.pengecoh ?? []).join(' • ')}</small>
                </div>
            {/if}
        </div>

    {:else if tipe === 'benar_salah' || tipe === 'setuju_tidak'}
        <div class="mt-1">
            {#each (d.pernyataan ?? []) as p, i}
                <div class="mb-1">
                    <span class="badge bg-secondary me-1">{i + 1}</span>
                    <Latex teks={p.teks ?? ''} />
                    <span class="badge bg-success ms-1">{p.jawaban ?? '-'}</span>
                </div>
            {/each}
        </div>

    {:else if tipe === 'isian_singkat'}
        <div class="mt-1"><Latex teks={String(d.kunci ?? '-')} /></div>

    {:else if tipe === 'uraian'}
        <div class="mt-1">
            <em>Pedoman penskoran:</em>
            <div><Latex teks={String(d.kunci ?? '-')} /></div>
        </div>

    {:else if tipe === 'mengurutkan'}
        <div class="mt-1">
            <em>Urutan benar:</em>
            <ol class="mb-0 mt-1">
                {#each (d.urutan_benar ?? d.urutan ?? []) as u}
                    <li><Latex teks={String(u)} /></li>
                {/each}
            </ol>
        </div>

    {:else if tipe === 'matrix_text' || tipe === 'matrix_checkbox'}
        <div class="mt-1 table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr><th></th>{#each (d.cols ?? []) as c}<th><Latex teks={String(c)} /></th>{/each}</tr>
                </thead>
                <tbody>
                    {#each (d.rows ?? []) as r, i}
                        <tr>
                            <th><Latex teks={String(r)} /></th>
                            {#each (d.cols ?? []) as _, j}
                                <td class="text-center">
                                    {#if d.kunci?.[i]?.[j]}
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    {:else}
                                        <span class="text-muted">–</span>
                                    {/if}
                                </td>
                            {/each}
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

    {:else if tipe === 'mengelompokkan'}
        <div class="mt-1">
            {#each (Array.isArray(d.kelompok) ? d.kelompok : Object.entries(d.kelompok ?? {}).map(([nama, items]) => ({ nama, items }))) as k}
                <div class="mb-1">
                    <span class="badge bg-primary me-1">{k.nama}</span>
                    <span>{Array.isArray(k.items) ? k.items.join(', ') : ''}</span>
                </div>
            {/each}
        </div>

    {:else if tipe === 'blank_space'}
        <div class="mt-1">
            {#if Array.isArray(d.kunci)}
                <ol class="mb-0">
                    {#each d.kunci as k}
                        <li><Latex teks={String(k)} /></li>
                    {/each}
                </ol>
            {:else}
                <Latex teks={String(d.kunci ?? '-')} />
            {/if}
        </div>

    {:else if tipe === 'image_labeling'}
        <div class="mt-1">
            {#each (d.markers ?? []) as m, i}
                <div class="mb-1">
                    <span class="badge bg-secondary me-1">Titik {i + 1}</span>
                    <Latex teks={m.label ?? m.kunci ?? '-'} />
                </div>
            {/each}
        </div>

    {:else if tipe === 'listening'}
        <div class="mt-1">
            {#if d.kunci}
                <Latex teks={String(d.kunci)} />
            {:else}
                <span class="text-muted">Lihat transkrip audio</span>
            {/if}
        </div>

    {:else}
        <!-- Fallback generik -->
        <div class="mt-1">
            {#if d.kunci !== undefined}
                {#if Array.isArray(d.kunci)}
                    {d.kunci.join(', ')}
                {:else if typeof d.kunci === 'object'}
                    <pre class="mb-0">{JSON.stringify(d.kunci, null, 2)}</pre>
                {:else}
                    <Latex teks={String(d.kunci)} />
                {/if}
            {:else}
                <span class="text-muted">Belum ada kunci jawaban</span>
            {/if}
        </div>
    {/if}
</div>
