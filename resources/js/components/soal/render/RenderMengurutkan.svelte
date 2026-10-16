<script lang="ts">
    import Latex from '@/components/Latex.svelte';

    let {
        detail,
        jawaban = $bindable([] as string[]),
        disabled = false,
    }: {
        detail: { urutan: string[] };
        jawaban?: string[];
        disabled?: boolean;
    } = $props();

    const safe = detail ?? {};

    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.urutan ?? []).length > 0) {
            initialized = true;
            if ((jawaban ?? []).length === 0) {
                jawaban = [...(safe.urutan ?? [])].sort(() => Math.random() - 0.5);
            }
        }
    });

    // Drag & drop state
    let dragIdx = $state<number | null>(null);
    let overIdx = $state<number | null>(null);
    let listRef: HTMLDivElement | null = null;

    function pindah(i: number, dir: number) {
        if (disabled) return;
        const j = i + dir;
        const jl = (jawaban ?? []).length;
        if (j < 0 || j >= jl) return;
        const arr = [...(jawaban ?? [])];
        [arr[i], arr[j]] = [arr[j], arr[i]];
        jawaban = arr;
    }

    // --- HTML5 drag (desktop) ---
    function onDragStart(e: DragEvent, i: number) {
        if (disabled) return;
        dragIdx = i;
        e.dataTransfer!.effectAllowed = 'move';
        try { e.dataTransfer!.setData('text/plain', String(i)); } catch {}
    }
    function onDragOver(e: DragEvent, i: number) {
        if (disabled || dragIdx === null) return;
        e.preventDefault();
        overIdx = i;
    }
    function onDrop(e: DragEvent, i: number) {
        if (disabled || dragIdx === null) return;
        e.preventDefault();
        const from = dragIdx;
        const to = i;
        if (from !== to) {
            const arr = [...(jawaban ?? [])];
            const [moved] = arr.splice(from, 1);
            arr.splice(to, 0, moved);
            jawaban = arr;
        }
        dragIdx = null;
        overIdx = null;
    }
    function onDragEnd() {
        dragIdx = null;
        overIdx = null;
    }

    // --- Touch drag (mobile): long-press lalu geser ---
    let touchDragIdx = $state<number | null>(null);
    let touchY = $state(0);
    let touchTimer: any = null;

    function onTouchStart(e: TouchEvent, i: number) {
        if (disabled) return;
        const t = e.touches[0];
        touchY = t.clientY;
        // Cegah scroll saat mulai sentuh item
        e.preventDefault();
        touchTimer = setTimeout(() => {
            touchDragIdx = i;
            dragIdx = i;
            if (navigator.vibrate) navigator.vibrate(30);
        }, 350);
    }
    function onTouchMove(e: TouchEvent) {
        if (touchDragIdx === null || !listRef) return;
        e.preventDefault();
        const t = e.touches[0];
        const els = listRef.querySelectorAll('.urut-item');
        els.forEach((el, idx) => {
            const r = (el as HTMLElement).getBoundingClientRect();
            if (t.clientY >= r.top && t.clientY <= r.bottom) {
                overIdx = idx;
            }
        });
    }
    function onTouchEnd() {
        clearTimeout(touchTimer);
        if (touchDragIdx !== null && overIdx !== null && touchDragIdx !== overIdx) {
            const arr = [...(jawaban ?? [])];
            const [moved] = arr.splice(touchDragIdx, 1);
            arr.splice(overIdx, 0, moved);
            jawaban = arr;
        }
        touchDragIdx = null;
        dragIdx = null;
        overIdx = null;
    }
</script>

<div class="alert alert-light border small mb-2">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Geser</strong> kartu untuk menyusun dari atas ke bawah sesuai urutan yang benar. Di HP: tahan lalu geser.
</div>

<div bind:this={listRef}>
    {#each (jawaban ?? []) as item, i}
        <div class="urut-item border rounded p-2 mb-2 d-flex gap-2 align-items-center bg-white
                {dragIdx === i ? 'urut-dragging' : ''} {overIdx === i && dragIdx !== i ? 'urut-over' : ''}"
            draggable={!disabled}
            ondragstart={(e) => onDragStart(e, i)}
            ondragover={(e) => onDragOver(e, i)}
            ondrop={(e) => onDrop(e, i)}
            ondragend={onDragEnd}
            ontouchstart={(e) => onTouchStart(e, i)}
            ontouchmove={onTouchMove}
            ontouchend={onTouchEnd}
            style="cursor:{disabled ? 'default' : 'grab'}; touch-action: none;">
            <span class="urut-handle text-muted flex-shrink-0" title="Geser">
                <i class="bi bi-grip-vertical"></i>
            </span>
            <span class="badge bg-primary flex-shrink-0">{i + 1}</span>
            <span class="flex-grow-1"><Latex teks={item} /></span>
            {#if !disabled}
                <span class="d-flex flex-column flex-shrink-0">
                    <button class="btn btn-sm btn-link p-0" onclick={() => pindah(i, -1)} disabled={i === 0} title="Naik" aria-label="Naik">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button class="btn btn-sm btn-link p-0" onclick={() => pindah(i, 1)} disabled={i === (jawaban ?? []).length - 1} title="Turun" aria-label="Turun">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </span>
            {/if}
        </div>
    {/each}
</div>

<style>
    .urut-item {
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        user-select: none;
        -webkit-user-select: none;
    }
    .urut-dragging {
        opacity: 0.5;
        border-color: #0d6efd !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }
    .urut-over {
        border-top: 3px solid #0d6efd !important;
    }
    .urut-handle {
        cursor: grab;
        font-size: 1.2rem;
    }
    .urut-handle:active {
        cursor: grabbing;
    }
</style>
