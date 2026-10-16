<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import RichTextEditor from '@/components/RichTextEditor.svelte';
    import SoalPilihanGanda from '@/components/soal/SoalPilihanGanda.svelte';
    import SoalMenjodohkan from '@/components/soal/SoalMenjodohkan.svelte';
    import SoalMengurutkan from '@/components/soal/SoalMengurutkan.svelte';
    import SoalPernyataan from '@/components/soal/SoalPernyataan.svelte';
    import SoalMatrix from '@/components/soal/SoalMatrix.svelte';
    import SoalMengelompokkan from '@/components/soal/SoalMengelompokkan.svelte';
    import SoalBlankSpace from '@/components/soal/SoalBlankSpace.svelte';
    import SoalImageLabeling from '@/components/soal/SoalImageLabeling.svelte';
    import SoalListening from '@/components/soal/SoalListening.svelte';

    let { kelompok, tipeSoalList }: {
        kelompok: { id: number; nama: string; kode: string | null; matpel: string };
        tipeSoalList: Record<string, string>;
    } = $props();

    const tipeOptions = Object.entries(tipeSoalList).map(([key, label]) => ({ key, label }));

    let tipeSoal = $state('pilihan_ganda');
    let pertanyaan = $state('');
    let poin = $state(1);
    let processing = $state(false);
    let errors = $state<Record<string, string>>({});

    // State per tipe
    let opsiList = $state([{ id: 'a', teks: '' }, { id: 'b', teks: '' }, { id: 'c', teks: '' }, { id: 'd', teks: '' }]);
    let kunciPG = $state('');
    let kunciKompleks = $state<string[]>([]);
    let pasanganList = $state([{ kiri: '', kanan: '' }, { kiri: '', kanan: '' }, { kiri: '', kanan: '' }]);
    let pengecohList = $state<string[]>([]);
    let kunciTeks = $state('');
    let urutanList = $state(['', '', '', '']);
    let pernyataanList = $state([{ teks: '', jawaban: '' }, { teks: '', jawaban: '' }, { teks: '', jawaban: '' }]);
    let matrixRows = $state(['', '']);
    let matrixCols = $state(['', '']);
    let matrixKunci = $state<string[][]>([['', ''], ['', '']]);
    let kelompokItems = $state([{ nama: '', items: [''] }, { nama: '', items: [''] }]);
    let blankTeks = $state('');
    let blankKunci = $state<string[]>(['']);
    let labelImageUrl = $state('');
    let labelMarkers = $state<{ x: number; y: number; label: string; kunci: string }[]>([]);
    let audioUrl = $state('');

    const isPG = $derived(['pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra'].includes(tipeSoal));

    function buildDetail(): Record<string, unknown> {
        switch (tipeSoal) {
            case 'pilihan_ganda':
            case 'pilihan_ganda_citra':
                return { opsi: opsiList.map(o => ({ teks: o.teks })), kunci: kunciPG };
            case 'pilihan_ganda_kompleks':
                return { opsi: opsiList.map(o => ({ teks: o.teks })), kunci: kunciKompleks };
            case 'menjodohkan':
            case 'menjodohkan_banyak':
                return {
                    pasangan: pasanganList.filter(p => p.kiri || p.kanan),
                    pengecoh: tipeSoal === 'menjodohkan_banyak' ? pengecohList.filter(p => p.trim() !== '') : [],
                };
            case 'isian_singkat':
            case 'uraian':
                return { kunci: kunciTeks };
            case 'mengurutkan':
                return { urutan: urutanList.filter(u => u.trim()) };
            case 'benar_salah':
            case 'setuju_tidak':
                return { pernyataan: pernyataanList.filter(p => p.teks) };
            case 'matrix_text':
            case 'matrix_checkbox':
                return { rows: matrixRows, cols: matrixCols, kunci: matrixKunci };
            case 'mengelompokkan':
                return { kelompok: kelompokItems };
            case 'blank_space':
                return { teks: blankTeks, kunci: blankKunci };
            case 'image_labeling':
                return { image: labelImageUrl, markers: labelMarkers };
            case 'listening':
                return { audio: audioUrl };
            default:
                return {};
        }
    }

    function simpan(kembali = false) {
        processing = true;
        errors = {};
        router.post(`/app/guru/bank-soal/kelompok/${kelompok.id}/soal`, {
            tipe: tipeSoal,
            pertanyaan,
            poin,
            detail: buildDetail(),
        }, {
            onError: (e) => { errors = e as Record<string, string>; processing = false; },
            onSuccess: () => {
                if (kembali) {
                    router.get(`/app/guru/bank-soal/kelompok/${kelompok.id}`);
                } else {
                    pertanyaan = '';
                    processing = false;
                }
            },
            onFinish: () => { processing = false; },
        });
    }
</script>

<svelte:head>
    <title>Tambah Soal — {kelompok.nama}</title>
</svelte:head>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">Tambah Soal</h5>
        <div class="text-muted small">{kelompok.nama}</div>
    </div>
    <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-9">
        <Card class="mb-3"><CardBody>
            <label class="form-label fw-semibold" for="tipe">Tipe soal</label>
            <select id="tipe" class="form-select" bind:value={tipeSoal}>
                {#each tipeOptions as t}<option value={t.key}>{t.label}</option>{/each}
            </select>
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="poin">Bobot / Poin</label>
                    <input id="poin" type="number" min="0.5" max="100" step="0.5" class="form-control" bind:value={poin} />
                    <div class="form-text">Bobot nilai soal ini</div>
                </div>
            </div>
        </CardBody></Card>

        <Card class="mb-3"><CardBody>
            <label class="form-label fw-semibold">Pertanyaan</label>
            <RichTextEditor id="pertanyaan" value={pertanyaan} placeholder="Tulis pertanyaan..." onchange={(h) => (pertanyaan = h)} />
            {#if errors.pertanyaan}<div class="text-danger small mt-1">{errors.pertanyaan}</div>{/if}
        </CardBody></Card>

        {#if isPG}
            <SoalPilihanGanda bind:opsiList bind:kunci={kunciPG} kompleks={tipeSoal === 'pilihan_ganda_kompleks'} bind:kunciKompleks />
        {/if}

        {#if tipeSoal === 'menjodohkan' || tipeSoal === 'menjodohkan_banyak'}
            <SoalMenjodohkan bind:pasanganList bind:pengecohList allowPengecoh={tipeSoal === 'menjodohkan_banyak'} />
        {/if}

        {#if tipeSoal === 'isian_singkat' || tipeSoal === 'uraian'}
            <Card class="mb-3"><CardBody>
                <label class="form-label fw-semibold" for="kunci">{tipeSoal === 'isian_singkat' ? 'Kunci jawaban' : 'Pedoman penskoran'}</label>
                <textarea id="kunci" class="form-control" rows="3" bind:value={kunciTeks}></textarea>
            </CardBody></Card>
        {/if}

        {#if tipeSoal === 'mengurutkan'}
            <SoalMengurutkan bind:urutanList />
        {/if}

        {#if tipeSoal === 'benar_salah' || tipeSoal === 'setuju_tidak'}
            <SoalPernyataan bind:pernyataanList tipe={tipeSoal as 'benar_salah' | 'setuju_tidak'} />
        {/if}

        {#if tipeSoal === 'matrix_text' || tipeSoal === 'matrix_checkbox'}
            <SoalMatrix bind:rows={matrixRows} bind:cols={matrixCols} bind:kunci={matrixKunci} mode={tipeSoal === 'matrix_text' ? 'text' : 'checkbox'} />
        {/if}

        {#if tipeSoal === 'mengelompokkan'}
            <SoalMengelompokkan bind:kelompokItems />
        {/if}

        {#if tipeSoal === 'blank_space'}
            <SoalBlankSpace bind:teks={blankTeks} bind:kunci={blankKunci} />
        {/if}

        {#if tipeSoal === 'image_labeling'}
            <SoalImageLabeling bind:imageUrl={labelImageUrl} bind:markers={labelMarkers} />
        {/if}

        {#if tipeSoal === 'listening'}
            <SoalListening bind:audioUrl />
        {/if}

        <div class="d-flex justify-content-end gap-2 mb-4">
            <Button color="outline-secondary" onclick={() => simpan(true)} disabled={processing || !pertanyaan}>
                Simpan & Kembali
            </Button>
            <Button color="primary" onclick={() => simpan(false)} disabled={processing || !pertanyaan}>
                {#if processing}<span class="spinner-border spinner-border-sm me-1"></span>{/if}
                <i class="bi bi-save me-1"></i>Simpan soal
            </Button>
        </div>
    </div>

    <div class="col-lg-3">
        <Card class="sticky-top" style="top: 1rem;"><CardBody>
            <h6 class="fw-bold mb-3">Informasi</h6>
            <div class="mb-2"><div class="text-muted small">Kode</div><div class="fw-semibold">{kelompok.kode ?? '-'}</div></div>
            <div class="mb-2"><div class="text-muted small">Matpel</div><div class="fw-semibold">{kelompok.matpel}</div></div>
            <div class="mb-2"><div class="text-muted small">Tipe soal</div><div class="fw-semibold">{tipeSoalList[tipeSoal] ?? tipeSoal}</div></div>
        </CardBody></Card>
    </div>
</div>
