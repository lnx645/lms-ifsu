<script lang="ts">
    import Latex from '@/components/Latex.svelte';

    let {
        detail,
        jawaban = $bindable({} as Record<number, string>),
        disabled = false,
        tipe = 'menjodohkan',
    }: {
        detail: { pasangan?: { kiri: string; kanan: string }[]; kiri?: string[]; kanan?: string[]; pengecoh?: string[] };
        jawaban?: Record<number, string>;
        disabled?: boolean;
        tipe?: string;
    } = $props();

    const safe = detail ?? {};
    // Format siswa: kiri & kanan terpisah (kunci di-strip). Format guru: pasangan.
    const pairs = $derived(
        safe.pasangan ?? (safe.kiri ? safe.kiri.map((k, i) => ({ kiri: k, kanan: '' })) : [])
    );

    // Opsi kanan diacak stabil per soal
    // Opsi kanan dengan ID unik (antisipasi teks kembar), shuffle stabil sekali
    let kananList = $state<{ id: string; teks: string }[]>([]);
    let shuffleDone = $state(false);
    $effect(() => {
        if (!shuffleDone && pairs.length > 0) {
            shuffleDone = true;
            const k = (safe.kanan ?? pairs.map((p: any) => p.kanan));
            const pengecoh = (safe.pengecoh ?? []) as string[];
            const semua = [...k, ...pengecoh];
            // beri ID unik: teks + index asli
            const withId = semua.map((teks, idx) => ({ id: `${teks}___${idx}`, teks }));
            // shuffle deterministik (seed dari detail) agar stabil antar reload
            let seed = 0;
            const seedStr = JSON.stringify(safe.kiri ?? safe.pasangan ?? '');
            for (let c = 0; c < seedStr.length; c++) seed = (seed * 31 + seedStr.charCodeAt(c)) >>> 0;
            const rand = () => { seed = (seed * 1103515245 + 12345) & 0x7fffffff; return seed / 0x7fffffff; };
            for (let i = withId.length - 1; i > 0; i--) {
                const j = Math.floor(rand() * (i + 1));
                [withId[i], withId[j]] = [withId[j], withId[i]];
            }
            kananList = withId;
        }
    });
    const strict = $derived(tipe === 'menjodohkan');
    const huruf = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    let selectedKiri = $state<number | null>(null);
    let containerRef: HTMLDivElement | null = null;
    let kiriRefs: (HTMLDivElement | null)[] = [];
    let kananRefs: (HTMLDivElement | null)[] = [];
    let lines = $state<{ x1: number; y1: number; x2: number; y2: number }[]>([]);
    const warnaGaris = ['#dc3545', '#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#d63384', '#20c997', '#ffc107'];
    let svgH = $state(0);

    const jbn = () => jawaban ?? {};

    function klikKiri(i: number) {
        if (disabled) return;
        if (jbn()[i] !== undefined) {
            const nj = { ...jbn() };
            delete nj[i];
            jawaban = nj;
            selectedKiri = null;
            requestAnimationFrame(drawLines);
            return;
        }
        selectedKiri = selectedKiri === i ? null : i;
    }

    function klikKanan(item: { id: string; teks: string }) {
        if (disabled || selectedKiri === null) return;
        // mode strict: opsi kanan tidak boleh dipakai 2x
        if (strict && (Object.values(jbn()) as string[]).includes(item.id)) return;
        jawaban = { ...jbn(), [selectedKiri]: item.id };
        selectedKiri = null;
        requestAnimationFrame(drawLines);
    }

    function drawLines() {
        if (!containerRef) return;
        const crect = containerRef.getBoundingClientRect();
        const nl: typeof lines = [];
        for (const [kiriIdxStr, kananId] of Object.entries(jbn())) {
            const kiriIdx = Number(kiriIdxStr);
            const kananIdx = kananList.findIndex(x => x.id === kananId);
            const elKiri = kiriRefs[kiriIdx];
            const elKanan = kananRefs[kananIdx];
            if (!elKiri || !elKanan) continue;
            const rk = elKiri.getBoundingClientRect();
            const rn = elKanan.getBoundingClientRect();
            nl.push({
                x1: rk.right - crect.left,
                y1: rk.top + rk.height / 2 - crect.top,
                x2: rn.left - crect.left,
                y2: rn.top + rn.height / 2 - crect.top,
            });
        }
        lines = nl;
        svgH = containerRef.scrollHeight;
    }

    $effect(() => {
        jbn();
        kananList;
        requestAnimationFrame(drawLines);
    });

    $effect(() => {
        const onResize = () => drawLines();
        window.addEventListener('resize', onResize);
        const t1 = setTimeout(drawLines, 150);
        const t2 = setTimeout(drawLines, 600); // setelah font/layout stabil
        return () => {
            window.removeEventListener('resize', onResize);
            clearTimeout(t1);
            clearTimeout(t2);
        };
    });
</script>

<div class="alert alert-light border small mb-2">
    <i class="bi bi-info-circle me-1"></i>
    Ketuk kotak <strong>kiri</strong>, lalu ketuk <strong>jawaban di kanan</strong> untuk menghubungkan dengan garis merah. Ketuk lagi kotak kiri untuk melepas.
</div>

<div bind:this={containerRef} class="menjodohkan-wrap position-relative">
    <svg class="menjodohkan-svg" style="height:{svgH}px" aria-hidden="true">
        {#each lines as l, i}
            {@const mx = (l.x1 + l.x2) / 2}
            <path d="M {l.x1} {l.y1} C {mx} {l.y1}, {mx} {l.y2}, {l.x2} {l.y2}"
                fill="none" stroke={warnaGaris[i % warnaGaris.length]} stroke-width="2.5" stroke-linecap="round" />
            <circle cx={l.x1} cy={l.y1} r="4" fill="#dc3545" />
            <circle cx={l.x2} cy={l.y2} r="4" fill="#dc3545" />
        {/each}
    </svg>

    <div class="row g-3">
        <div class="col-5">
            {#each pairs as p, i}
                <div bind:this={kiriRefs[i]}
                    class="mj-item d-flex align-items-center gap-2 border rounded p-2 mb-3 bg-white {selectedKiri === i ? 'mj-selected' : ''} {jbn()[i] !== undefined ? 'mj-connected' : ''}"
                    style="cursor:{disabled ? 'default' : 'pointer'}"
                    onclick={() => klikKiri(i)} role="button" tabindex="0"
                    onkeydown={(e) => { if (e.key === 'Enter') klikKiri(i); }}>
                    <span class="badge bg-secondary flex-shrink-0">{i + 1}</span>
                    <span class="flex-grow-1"><Latex teks={p.kiri ?? ''} /></span>
                    {#if jbn()[i] !== undefined}
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                    {/if}
                </div>
            {/each}
        </div>
        <div class="col-7">
            {#each kananList as item, j}
                {@const terpakai = (Object.values(jbn()) as string[]).includes(item.id)}
                <div bind:this={kananRefs[j]}
                    class="mj-item d-flex align-items-center gap-2 border rounded p-2 mb-3 bg-white {terpakai ? 'mj-used' : ''}"
                    style="cursor:{disabled || selectedKiri === null ? 'default' : 'pointer'}"
                    onclick={() => klikKanan(item)} role="button" tabindex="0"
                    onkeydown={(e) => { if (e.key === 'Enter') klikKanan(item); }}>
                    <span class="badge bg-light text-dark border flex-shrink-0">{huruf[j] ?? j + 1}</span>
                    <span class="flex-grow-1"><Latex teks={item.teks ?? ''} /></span>
                </div>
            {/each}
        </div>
    </div>
</div>

<style>
    .menjodohkan-wrap {
        min-height: 100px;
    }
    .menjodohkan-svg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        pointer-events: none;
        z-index: 5;
    }
    .mj-item {
        position: relative;
        z-index: 1;
        transition: border-color 0.15s, box-shadow 0.15s;
        min-height: 48px;
    }
    .mj-item span.flex-grow-1 {
        overflow-wrap: break-word;
        word-break: break-word;
        hyphens: auto;
        line-height: 1.4;
        font-size: 0.9rem;
    }
    /* Di layar kecil, teks lebih kecil agar muat */
    @media (max-width: 576px) {
        .mj-item span.flex-grow-1 {
            font-size: 0.82rem;
        }
        .mj-item {
            padding: 0.4rem !important;
        }
    }
    /* Latex di dalam mj-item jangan overflow */
    .mj-item :global(.katex) {
        font-size: 0.9em;
        white-space: normal;
    }
    .mj-item :global(.katex-display) {
        margin: 0.3em 0;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .mj-selected {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }
    .mj-connected {
        border-color: #198754 !important;
    }
    .mj-used {
        opacity: 0.55;
    }
</style>
