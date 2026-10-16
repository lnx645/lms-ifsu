<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import type {
        GuruKelasInfo,
        PenilaianInfo,
        SiswaNilaiItem,
    } from '@/types/models';

    let {
        penilaian,
        guruKelas,
        siswas,
    }: {
        penilaian: PenilaianInfo;
        guruKelas: GuruKelasInfo;
        siswas: SiswaNilaiItem[];
    } = $props();

    let nilaiDraft = $state<Record<string, string>>({});
    let ketDraft = $state<Record<string, string>>({});
    let menyimpan = $state<Record<string, boolean>>({});
    let errorPesan = $state('');

    function simpanNilai(siswa: SiswaNilaiItem) {
        const nilai = nilaiDraft[siswa.nisn] ?? '';
        const nilaiAngka = Number(nilai);
        if (nilai === '' || Number.isNaN(nilaiAngka)) {
            errorPesan = `Isi nilai untuk ${siswa.nama} dulu.`;
            return;
        }
        if (nilaiAngka < 0 || nilaiAngka > penilaian.nilai_maks) {
            errorPesan = `Nilai harus 0–${penilaian.nilai_maks}.`;
            return;
        }
        errorPesan = '';
        menyimpan[siswa.nisn] = true;
        router.post(
            PenilaianController.store({
                penilaian: penilaian.id,
                guruKelas: guruKelas.id,
            }).url,
            {
                siswa_nisn: siswa.nisn,
                nilai,
                keterangan: ketDraft[siswa.nisn] ?? '',
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    menyimpan[siswa.nisn] = false;
                },
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title={penilaian.nama}
        subtitle={`${guruKelas.kelas ?? 'Kelas'} · ${guruKelas.matpel ?? 'Matpel'} — nilai maksimal ${penilaian.nilai_maks}`}
    >
        {#snippet actions()}
            <a
                use:inertia
                href={PenilaianController.index().url}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Daftar Penilaian
            </a>
        {/snippet}
    </PageHeader>

    {#if errorPesan}
        <div class="alert alert-danger py-2 small mb-3">
            <i class="bi bi-exclamation-circle me-1"></i>{errorPesan}
        </div>
    {/if}

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            {#if siswas.length === 0}
                <EmptyState
                    icon="bi-people"
                    message="Tidak ada siswa aktif di penugasan ini."
                    variant="card"
                />
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>NISN</th>
                                <th>Nilai / {penilaian.nilai_maks}</th>
                                <th>Keterangan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each siswas as siswa, i (siswa.nisn)}
                                <tr>
                                    <td class="text-muted">{i + 1}</td>
                                    <td class="fw-semibold">{siswa.nama}</td>
                                    <td class="text-muted">{siswa.nisn}</td>
                                    <td>
                                        <div
                                            class="d-flex align-items-center gap-1"
                                        >
                                            <input
                                                type="number"
                                                class="form-control form-control-sm"
                                                style="width: 90px"
                                                min="0"
                                                max={penilaian.nilai_maks}
                                                value={nilaiDraft[
                                                    siswa.nisn
                                                ] ??
                                                    (siswa.nilai ?? '')}
                                                disabled={menyimpan[
                                                    siswa.nisn
                                                ]}
                                                oninput={(e) =>
                                                    (nilaiDraft[
                                                        siswa.nisn
                                                    ] = (
                                                        e.currentTarget as HTMLInputElement
                                                    ).value)}
                                            />
                                            {#if siswa.sumber === 'tugas'}
                                                <Badge
                                                    color="info"
                                                    pill
                                                    title="Nilai berasal dari tugas"
                                                    >Dari Tugas</Badge
                                                >
                                            {/if}
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            style="min-width: 160px"
                                            maxlength="255"
                                            placeholder="Opsional"
                                            value={ketDraft[siswa.nisn] ??
                                                (siswa.keterangan ??
                                                    '')}
                                            disabled={menyimpan[
                                                siswa.nisn
                                            ]}
                                            oninput={(e) =>
                                                (ketDraft[siswa.nisn] =
                                                    (
                                                        e.currentTarget as HTMLInputElement
                                                    ).value)}
                                        />
                                    </td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success text-nowrap"
                                            disabled={menyimpan[
                                                siswa.nisn
                                            ]}
                                            onclick={() =>
                                                simpanNilai(siswa)}
                                        >
                                            {#if menyimpan[siswa.nisn]}
                                                <span
                                                    class="spinner-border spinner-border-sm"
                                                ></span>
                                            {:else}
                                                <i
                                                    class="bi bi-check-lg me-1"
                                                ></i>
                                                Simpan
                                            {/if}
                                        </button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </CardBody>
    </Card>
</div>