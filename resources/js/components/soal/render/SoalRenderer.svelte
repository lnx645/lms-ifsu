<script lang="ts">
    // Dispatcher: render soal sesuai tipe. Reusable untuk preview guru & ujian siswa.
    import RenderPilihanGanda from './RenderPilihanGanda.svelte';
    import RenderMenjodohkan from './RenderMenjodohkan.svelte';
    import RenderIsian from './RenderIsian.svelte';
    import RenderMengurutkan from './RenderMengurutkan.svelte';
    import RenderPernyataan from './RenderPernyataan.svelte';
    import RenderMatrix from './RenderMatrix.svelte';
    import RenderBlankSpace from './RenderBlankSpace.svelte';
    import RenderImageLabeling from './RenderImageLabeling.svelte';
    import RenderListening from './RenderListening.svelte';
    import RenderMengelompokkan from './RenderMengelompokkan.svelte';
    import KunciJawaban from './KunciJawaban.svelte';

    let {
        tipe,
        detail,
        jawaban = $bindable(null),
        disabled = false,
        showKunci = false,
    }: {
        tipe: string;
        detail: Record<string, any> | null;
        jawaban?: any;
        disabled?: boolean;
        showKunci?: boolean;
    } = $props();

    const d = detail ?? {};
    // Mapping tipe lama ke baru
    const tipeNorm = tipe === 'pg' ? 'pilihan_ganda'
        : tipe === 'multi' ? 'pilihan_ganda_kompleks'
        : tipe === 'isian' ? 'isian_singkat'
        : tipe === 'esai' ? 'uraian'
        : tipe;
    const isEmpty = !detail || Object.keys(detail).length === 0;
</script>

{#if isEmpty}
    <div class="alert alert-warning mb-0">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Data soal belum lengkap. Silakan edit soal ini untuk melengkapi datanya.
    </div>
{:else}

{#if tipeNorm === 'pilihan_ganda' || tipe === 'pilihan_ganda_citra'}
    <RenderPilihanGanda detail={d} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'pilihan_ganda_kompleks'}
    <RenderPilihanGanda detail={d} kompleks bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'menjodohkan' || tipe === 'menjodohkan_banyak'}
    <RenderMenjodohkan detail={d} bind:jawaban disabled={disabled} tipe={tipe} />
{:else if tipeNorm === 'isian_singkat'}
    <RenderIsian bind:jawaban disabled={disabled} placeholder="Tulis jawaban singkat..." />
{:else if tipeNorm === 'uraian'}
    <RenderIsian bind:jawaban disabled={disabled} rows={4} placeholder="Tulis uraian jawaban..." />
{:else if tipeNorm === 'mengurutkan'}
    <RenderMengurutkan detail={d} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'benar_salah' || tipe === 'setuju_tidak'}
    <RenderPernyataan detail={d} tipe={tipe} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'matrix_text'}
    <RenderMatrix detail={d} mode="text" bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'matrix_checkbox'}
    <RenderMatrix detail={d} mode="checkbox" bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'mengelompokkan'}
    <RenderMengelompokkan detail={d} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'blank_space'}
    <RenderBlankSpace detail={d} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'image_labeling'}
    <RenderImageLabeling detail={d} bind:jawaban disabled={disabled} />
{:else if tipeNorm === 'listening'}
    <RenderListening detail={d} bind:jawaban disabled={disabled} />
{:else}
    <div class="alert alert-warning">Tipe soal "{tipe}" belum didukung renderer.</div>
{/if}

{#if showKunci}
    <KunciJawaban tipe={tipe} detail={d} />
{/if}
{/if}
