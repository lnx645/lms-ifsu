<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { Card, CardBody, Button, Badge, Modal, ModalBody, ModalFooter } from '@sveltestrap/sveltestrap';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type Opsi = { id: number; teks: string };
    type Soal = {
        id: number;
        tipe: string;
        pertanyaan: string;
        poin: number;
        media_path: string | null;
        media_type: string | null;
        opsis: Opsi[];
        jawaban_tersimpan: { opsi_dipilih: number[] | null; jawaban_teks: string | null } | null;
    };

    let { ujian, pengerjaan, soals }: {
        ujian: { id: number; judul: string; durasi_menit: number };
        pengerjaan: { id: number; batas_at: string; server_time: string };
        soals: Soal[];
    } = $props();

    let aktifIndex = $state(0);
    let jawaban = $state<Record<number, { opsi_dipilih: number[]; jawaban_teks: string }>>({});
    let sisaDetik = $state(0);
    let showSubmitModal = $state(false);
    let submitting = $state(false);
    let interval: ReturnType<typeof setInterval>;

    // Inisialisasi jawaban tersimpan
    for (const s of soals) {
        jawaban[s.id] = {
            opsi_dipilih: s.jawaban_tersimpan?.opsi_dipilih ?? [],
            jawaban_teks: s.jawaban_tersimpan?.jawaban_teks ?? '',
        };
    }

    function hitungSisa() {
        const batas = new Date(pengerjaan.batas_at).getTime();
        const kini = Date.now() + offsetJam;
        sisaDetik = Math.max(0, Math.floor((batas - kini) / 1000));
        if (sisaDetik <= 0) {
            clearInterval(interval);
            kumpulkan(true);
        }
    }

    // Offset jam server vs perangkat
    const offsetJam = new Date(pengerjaan.server_time).getTime() - Date.now();

    onMount(() => {
        hitungSisa();
        interval = setInterval(hitungSisa, 1000);
    });
    onDestroy(() => clearInterval(interval));

    const jam = $derived(Math.floor(sisaDetik / 3600));
    const menit = $derived(Math.floor((sisaDetik % 3600) / 60));
    const detik = $derived(sisaDetik % 60);
    const waktuHabis = $derived(sisaDetik < 300);

    const soalAktif = $derived(soals[aktifIndex]);
    const terjawab = $derived(soals.filter((s) => {
        const j = jawaban[s.id];
        return j && (j.opsi_dipilih.length > 0 || j.jawaban_teks.trim() !== '');
    }).length);

    let saveTimer: ReturnType<typeof setTimeout>;
    function simpanOtomatis(soalId: number) {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const j = jawaban[soalId];
            router.post(SiswaUjianController.jawaban.url(ujian.id), {
                soal_id: soalId,
                opsi_dipilih: j.opsi_dipilih,
                jawaban_teks: j.jawaban_teks,
            }, { preserveState: true, preserveScroll: true });
        }, 800);
    }

    function pilihOpsi(soal: Soal, opsiId: number) {
        const j = jawaban[soal.id];
        if (soal.tipe === 'pg_kompleks') {
            j.opsi_dipilih = j.opsi_dipilih.includes(opsiId)
                ? j.opsi_dipilih.filter((x) => x !== opsiId)
                : [...j.opsi_dipilih, opsiId];
        } else {
            j.opsi_dipilih = [opsiId];
        }
        simpanOtomatis(soal.id);
    }

    function kumpulkan(otomatis = false) {
        submitting = true;
        router.post(SiswaUjianController.selesai.url(ujian.id), {}, {
            onFinish: () => { submitting = false; showSubmitModal = false; },
        });
    }
</script>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white py-2 border-bottom">
        <h5 class="fw-bold mb-0">{ujian.judul}</h5>
        <Badge color={waktuHabis ? 'danger' : 'primary'} class="fs-6">
            {String(jam).padStart(2, '0')}:{String(menit).padStart(2, '0')}:{String(detik).padStart(2, '0')}
        </Badge>
    </div>

    <div class="row g-3">
        <div class="col-md-9">
            {#if soalAktif}
                <Card>
                    <CardBody>
                        <div class="d-flex justify-content-between mb-3">
                            <Badge color="secondary">Soal {aktifIndex + 1} / {soals.length}</Badge>
                            <Badge color="info">{soalAktif.poin} poin</Badge>
                        </div>
                        <div class="mb-3" style="white-space: pre-line;">{soalAktif.pertanyaan}</div>

                        {#if soalAktif.tipe === 'pg' || soalAktif.tipe === 'benar_salah' || soalAktif.tipe === 'pg_kompleks'}
                            <div class="d-grid gap-2">
                                {#each soalAktif.opsis as o (o.id)}
                                    {@const dipilih = jawaban[soalAktif.id].opsi_dipilih.includes(o.id)}
                                    <button
                                        type="button"
                                        class="btn text-start {dipilih ? 'btn-primary' : 'btn-outline-secondary'}"
                                        onclick={() => pilihOpsi(soalAktif, o.id)}
                                    >{o.teks}</button>
                                {/each}
                            </div>
                        {:else if soalAktif.tipe === 'isian'}
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Ketik jawaban..."
                                bind:value={jawaban[soalAktif.id].jawaban_teks}
                                oninput={() => simpanOtomatis(soalAktif.id)}
                            />
                        {:else}
                            <textarea
                                class="form-control"
                                rows="5"
                                placeholder="Ketik jawaban esai..."
                                bind:value={jawaban[soalAktif.id].jawaban_teks}
                                oninput={() => simpanOtomatis(soalAktif.id)}
                            ></textarea>
                        {/if}

                        <div class="d-flex justify-content-between mt-4">
                            <Button color="outline-secondary" disabled={aktifIndex === 0} onclick={() => aktifIndex--}>Sebelumnya</Button>
                            {#if aktifIndex < soals.length - 1}
                                <Button color="primary" onclick={() => aktifIndex++}>Berikutnya</Button>
                            {:else}
                                <Button color="success" onclick={() => (showSubmitModal = true)}>Kumpulkan</Button>
                            {/if}
                        </div>
                    </CardBody>
                </Card>
            {/if}
        </div>

        <div class="col-md-3">
            <Card class="sticky-top" style="top: 70px;">
                <CardBody>
                    <h6 class="fw-bold">Navigasi Soal</h6>
                    <p class="small text-muted">{terjawab}/{soals.length} terjawab</p>
                    <div class="d-flex flex-wrap gap-1">
                        {#each soals as s, i (s.id)}
                            {@const j = jawaban[s.id]}
                            {@const ok = j && (j.opsi_dipilih.length > 0 || j.jawaban_teks.trim() !== '')}
                            <button
                                type="button"
                                class="btn btn-sm {i === aktifIndex ? 'btn-dark' : ok ? 'btn-success' : 'btn-outline-secondary'}"
                                style="width: 38px;"
                                onclick={() => (aktifIndex = i)}
                            >{i + 1}</button>
                        {/each}
                    </div>
                    <Button color="success" block class="mt-3" onclick={() => (showSubmitModal = true)}>Kumpulkan Jawaban</Button>
                </CardBody>
            </Card>
        </div>
    </div>
</div>

<Modal isOpen={showSubmitModal} toggle={() => (showSubmitModal = false)}>
    <ModalBody>
        <h6 class="fw-bold">Kumpulkan ujian?</h6>
        <p>Kamu menjawab {terjawab} dari {soals.length} soal. Setelah dikumpulkan, jawaban tidak bisa diubah.</p>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => (showSubmitModal = false)}>Batal</Button>
        <Button color="success" onclick={() => kumpulkan(false)} disabled={submitting}>
            {submitting ? 'Mengumpulkan...' : 'Ya, Kumpulkan'}
        </Button>
    </ModalFooter>
</Modal>
