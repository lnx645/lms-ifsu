<script lang="ts">
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Input,
        FormGroup,
        Label,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';
    import NilaiSlider from '@/components/NilaiSlider.svelte';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { useForm, router, inertia } from '@inertiajs/svelte';
    import { predikat } from '@/lib/nilai';
    import type { GuruKelasInfo } from '@/types/models';

    interface Penilaian {
        id: number;
        nama: string;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
    }

    interface Siswa {
        nisn: string;
        nama_lengkap: string;
    }

    interface Detail {
        id: number;
        nilai: number;
        keterangan: string | null;
    }

    interface Props {
        penilaian: Penilaian;
        guruKelas: GuruKelasInfo;
        siswa: Siswa;
        detail: Detail | null;
    }

    let { penilaian, guruKelas, siswa, detail }: Props = $props();

    const form = useForm({
        nilai: detail?.nilai ?? ('' as number | ''),
        keterangan: detail?.keterangan ?? '',
    });

    const predikatSaatIni = (): ReturnType<typeof predikat> =>
        predikat(detail?.nilai ?? null, penilaian.nilai_maks || 100);

    const nilaiMaks = () => Number(penilaian?.nilai_maks ?? 0);

    function backUrl() {
        const url = new URL(
            DetailPenilaianController.filterSiswa({
                penilaian: penilaian.id,
            }).url,
            window.location.origin,
        );
        url.searchParams.set('guru_kelas_id', String(guruKelas.id));
        return url.pathname + url.search;
    }

    function handleSubmit() {
        form.post(
            DetailPenilaianController.storeNilai({
                penilaian: penilaian.id,
                guruKelas: guruKelas.id,
                siswa: siswa.nisn,
            }).url,
            {
                preserveScroll: true,
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Input Nilai Siswa"
        subtitle={`${penilaian?.nama ?? 'Penilaian'} · ${guruKelas?.kelas ?? 'Kelas'} · ${guruKelas?.matpel ?? 'Matpel'}`}
    >
        {#snippet actions()}
            <a
                use:inertia
                href={backUrl()}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Daftar Siswa
            </a>
        {/snippet}
    </PageHeader>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <Card class="border rounded-1 shadow-none h-100">
                <CardBody class="p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <Avatar name={siswa?.nama_lengkap ?? ''} size={52} />
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">
                                {siswa?.nama_lengkap}
                            </div>
                            <div class="text-muted small">NISN: {siswa?.nisn}</div>
                        </div>
                    </div>
                    <div class="small text-muted">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Penilaian</span>
                            <span class="fw-semibold text-body">{penilaian?.nama}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Tipe</span>
                            <span class="fw-semibold text-body">{penilaian?.tipe}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Nilai Maksimum</span>
                            <span class="fw-semibold text-body">{penilaian?.nilai_maks}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Status</span>
                            <span>
                                {#if penilaian?.aktif}
                                    <Badge color="success" pill>Aktif</Badge>
                                {:else}
                                    <Badge color="secondary" pill>Nonaktif</Badge>
                                {/if}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Nilai Saat Ini</span>
                            <span class="text-end">
                                {#if detail?.nilai !== null && detail?.nilai !== undefined}
                                    {@const p = predikatSaatIni()}
                                    <span class="fw-bold text-success">
                                        {detail.nilai} / {penilaian?.nilai_maks}
                                    </span>
                                    {#if p}
                                        <div>
                                            <span class="small {p.kelas}">
                                                Predikat {p.huruf} · {p.label}
                                            </span>
                                        </div>
                                    {/if}
                                {:else}
                                    <span class="text-muted">Belum dinilai</span>
                                {/if}
                            </span>
                        </div>
                    </div>
                </CardBody>
            </Card>
        </div>

        <div class="col-12 col-lg-8">
            <Card class="border rounded-1 shadow-none h-100">
                <CardBody class="p-4">
                    <div class="fw-semibold mb-1">
                        <i class="bi bi-pencil-square me-1 text-primary"></i>
                        Isi Nilai
                    </div>
                    <p class="text-muted small mb-4">
                        Nilai maksimal {penilaian?.nilai_maks} — geser slider atau ketik
                        langsung.
                    </p>

                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            handleSubmit();
                        }}
                        novalidate
                    >
                        <NilaiSlider
                            value={form.nilai}
                            max={nilaiMaks()}
                            error={form.errors.nilai}
                            disabled={form.processing}
                            onchange={(v) => (form.nilai = v)}
                        />

                        <FormGroup class="mt-3">
                            <Label for="keterangan" class="fw-semibold">
                                Keterangan <span class="text-muted fw-normal">(opsional)</span>
                            </Label>
                            <Input
                                id="keterangan"
                                type="text"
                                value={form.keterangan}
                                oninput={(e) =>
                                    (form.keterangan = (
                                        e.currentTarget as HTMLInputElement
                                    ).value)}
                                placeholder="Misal: Remidi, tidak ikut ulangan…"
                                disabled={form.processing}
                            />
                            {#if form.errors.keterangan}
                                <small class="text-danger d-block mt-1">
                                    {form.errors.keterangan}
                                </small>
                            {/if}
                        </FormGroup>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                            <Button
                                color="secondary"
                                outline
                                size="sm"
                                onclick={() => router.visit(backUrl())}
                                disabled={form.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                color="primary"
                                size="sm"
                                type="submit"
                                disabled={form.processing}
                            >
                                {#if form.processing}
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                {:else}
                                    <i class="bi bi-check-lg me-1"></i>
                                {/if}
                                {detail?.nilai !== null && detail?.nilai !== undefined
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Nilai'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    </div>
</div>
