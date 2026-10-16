<script lang="ts">
    let { src, alt = 'Gambar soal' }: { src: string; alt?: string } = $props();

    let showModal = $state(false);
    let scale = $state(1);
    let translateX = $state(0);
    let translateY = $state(0);

    let startDist = 0;
    let startScale = 1;
    let startX = 0;
    let startY = 0;
    let lastX = 0;
    let lastY = 0;

    function openModal() {
        showModal = true;
        scale = 1;
        translateX = 0;
        translateY = 0;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        showModal = false;
        document.body.style.overflow = '';
    }

    function getDist(touches: TouchList): number {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    }

    function onTouchStart(e: TouchEvent) {
        if (e.touches.length === 2) {
            startDist = getDist(e.touches);
            startScale = scale;
        } else if (e.touches.length === 1) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            lastX = translateX;
            lastY = translateY;
        }
    }

    function onTouchMove(e: TouchEvent) {
        e.preventDefault();
        if (e.touches.length === 2) {
            const dist = getDist(e.touches);
            scale = Math.min(4, Math.max(1, startScale * (dist / startDist)));
        } else if (e.touches.length === 1 && scale > 1) {
            translateX = lastX + (e.touches[0].clientX - startX);
            translateY = lastY + (e.touches[0].clientY - startY);
        }
    }

    function zoomIn() {
        scale = Math.min(4, scale + 0.5);
    }
    function zoomOut() {
        scale = Math.max(1, scale - 0.5);
        if (scale === 1) {
            translateX = 0;
            translateY = 0;
        }
    }
    function reset() {
        scale = 1;
        translateX = 0;
        translateY = 0;
    }
</script>

<!-- Thumbnail: ketuk untuk zoom -->
<img src={src} alt={alt} class="zoomable-img img-fluid rounded border"
    style="cursor:zoom-in; touch-action: pinch-zoom;"
    onclick={openModal} onkeydown={(e) => { if (e.key === 'Enter') openModal(); }}
    tabindex="0" role="button" />

{#if showModal}
    <div class="zoom-modal" onclick={closeModal} role="dialog" aria-modal="true" tabindex="-1"
        onkeydown={(e) => { if (e.key === 'Escape') closeModal(); }}>
        <div class="zoom-controls" onclick={(e) => e.stopPropagation()}>
            <button class="btn btn-sm btn-light" onclick={zoomIn} title="Perbesar"><i class="bi bi-zoom-in"></i></button>
            <button class="btn btn-sm btn-light" onclick={zoomOut} title="Perkecil"><i class="bi bi-zoom-out"></i></button>
            <button class="btn btn-sm btn-light" onclick={reset} title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="btn btn-sm btn-danger" onclick={closeModal} title="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="zoom-canvas"
            ontouchstart={onTouchStart} ontouchmove={onTouchMove}
            onclick={(e) => e.stopPropagation()}>
            <img src={src} alt={alt} draggable="false"
                style="transform: translate({translateX}px, {translateY}px) scale({scale});" />
        </div>
        <div class="zoom-hint">Cubit untuk zoom • Geser untuk mengintip • Ketuk latar untuk tutup</div>
    </div>
{/if}

<style>
    .zoomable-img {
        max-height: 300px;
        object-fit: contain;
    }
    .zoom-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.92);
        z-index: 9999;
        display: flex;
        flex-direction: column;
    }
    .zoom-controls {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        padding: 0.75rem;
        z-index: 2;
    }
    .zoom-canvas {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        touch-action: none;
    }
    .zoom-canvas img {
        max-width: 100%;
        max-height: 100%;
        transition: transform 0.1s;
        user-select: none;
        -webkit-user-drag: none;
    }
    .zoom-hint {
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        padding: 0.5rem;
    }
</style>
