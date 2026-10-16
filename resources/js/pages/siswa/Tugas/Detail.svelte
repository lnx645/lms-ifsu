<script lang="ts">
    import { inertia, useForm } from '@inertiajs/svelte';
    import {
        Alert,
        Badge,
        Button,
        Card,
        CardBody,
    } from '@sveltestrap/sveltestrap';
    import { formatBytes } from '@/lib/materi';
    import {
        ACCEPT_ATTRIBUTE,
        PENGGUMPULAN_INFO,
        STATUS_TUGAS_INFO,
        validasiFile,
    } from '@/lib/tugas';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';

    type TugasDetail = {
        id: number;
        judul: string;
        deskripsi: string | null;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        tanggal_terbit: string | null;
        deadline: string | null;
        deadline_at: string | null;
        jenis_pengumpulan: keyof typeof PENGGUMPULAN_INFO;
        file_name: string | null;
        file_size: number;
        mime_type: string | null;
        poin: number;
        status: keyof typeof STATUS_TUGAS_INFO;
    };

    type PengumpulanInfo = {
        id: number;
        file_name: string | null;
        file_size: number;
        jawaban_teks: string | null;
        submitted_at: string;
        terlambat: boolean;
        nilai: number | null;
    };

    let {
        tugas,
        pengumpulan,
    }: {
        tugas: TugasDetail;
        pengumpulan: PengumpulanInfo | null;
    } = $props();

    const form = useForm({
        file: null as File | null,
        jawaban_teks: '',
    });

    let fileError = $state('');
    let teksError = $state('');

    // svelte-ignore state_referenced_locally
    let deadlineLewat = $state(
        !!tugas.deadline_at &&
            new Date(tugas.deadline_at).getTime() < Date.now(),
    );

    function setFile(file: File | null) {
        fileError = '';
        const error = validasiFile(file);
        if (error) {
            fileError = error;
            form.file = null;
            return;
        }
        form.file = file;
    }

    function kumpulkan() {
        const butuhFile = tugas.jenis_pengumpulan !== 'teks';
        const butuhTeks = tugas.jenis_pengumpulan !== 'file';
        const teksAda = form.jawaban_teks.trim().length > 0;

        if (butuhFile && !form.file) {
            fileError = 'Pilih berkas jawaban dulu.';
        }
        if (butuhTeks && !teksAda) {
            teksError = 'Tulis jawabanmu dulu.';
        }
        if ((butuhFile && !form.file) || (butuhTeks && !teksAda)) {
            return;
        }
        form.post(TugasController.kumpul({ tugas: tugas.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                form.file = null;
                form.jawaban_teks = '';
                fileError = '';
                teksError = '';
            },
        });
    }
</script>

<div class="container-fluid px-0">
    <div class="tugas-header mb-3">
        <a
            use:inertia
            href={TugasController.index().url}
            class="btn btn-sm btn-outline-secondary mb-2"
        >
            <i class="bi bi-arrow-left me-1"></i>Daftar Tugas
        </a>

        <div class="d-flex gap-2 flex-wrap mb-2">
            <Badge color="primary">{tugas.matpel ?? 'Matpel'}</Badge>
            <Badge color="secondary">{tugas.kelas ?? 'Kelas'}</Badge>
            <Badge color="info">
                <i class={`bi ${PENGGUMPULAN_INFO[tugas.jenis_pengumpulan].icon} me-1`}></i>{PENGGUMPULAN_INFO[
                    tugas.jenis_pengumpulan
                ].label}
            </Badge>
        </div>
        <h1 class="h4 fw-bold mb-1 text-break">{tugas.judul}</h1>
        <div class="text-muted small">
            <i class="bi bi-person me-1"></i>{tugas.guru ?? 'Guru'}
            <span class="mx-1">•</span>
            <i class="bi bi-calendar3 me-1"></i>Terbit: {tugas.tanggal_terbit ?? 'Langsung'}
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3 p-md-4">
                    <div class="section-title mb-1">Petunjuk</div>

                    {#if tugas.deskripsi}
                        <div class="rich-deskripsi text-md mb-4">
                            {@html tugas.deskripsi}
                        </div>
                    {:else}
                        <div class="text-muted small mb-4">
                            Tidak ada petunjuk tambahan untuk tugas
                            ini.
                        </div>
                    {/if}

                    {#if tugas.file_name}
                        <a
                            href={TugasController.unduh({
                                tugas: tugas.id,
                            }).url}
                            class="lampiran d-inline-flex align-items-center gap-2 border rounded-1 px-3 py-2 text-decoration-none"
                        >
                            <i class="bi bi-paperclip text-primary"
                            ></i>
                            <span class="text-truncate fw-semibold"
                                >{tugas.file_name}</span
                            >
                            <span class="text-muted text-nowrap"
                                >({formatBytes(
                                    tugas.file_size,
                                )})</span
                            >
                        </a>
                    {/if}
                </CardBody>
            </Card>
        </div>

        <div class="col-lg-5">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3 p-md-4">
                    <div
                        class="d-flex justify-content-between align-items-center gap-2 mb-3"
                    >
                        <div class="section-title mb-0">
                            Tugas Anda
                        </div>
                        <Badge
                            color={STATUS_TUGAS_INFO[tugas.status]
                                .color}
                            pill
                        >
                            <i
                                class={`bi ${STATUS_TUGAS_INFO[tugas.status].icon} me-1`}
                            ></i>
                            {STATUS_TUGAS_INFO[tugas.status].label}
                        </Badge>
                    </div>

                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i
                            class={`bi bi-hourglass-split ${deadlineLewat ? 'text-danger' : 'text-primary'}`}
                        ></i>
                        <div>
                            <div class="small text-muted">
                                Batas Waktu Pengumpulan
                            </div>
                            <div
                                class={`fw-semibold ${deadlineLewat ? 'text-danger' : ''}`}
                            >
                                {tugas.deadline ?? '—'}
                            </div>
                        </div>
                    </div>

                    <div
                        class="small text-muted d-flex align-items-center gap-1 mb-3"
                    >
                        <i
                            class={`bi ${PENGGUMPULAN_INFO[tugas.jenis_pengumpulan].icon} ${PENGGUMPULAN_INFO[tugas.jenis_pengumpulan].warna}`}
                        ></i>
                        Kumpul: {PENGGUMPULAN_INFO[
                            tugas.jenis_pengumpulan
                        ].label}
                    </div>

                    {#if pengumpulan}
                        <div
                            class="border rounded-1 p-3 mb-3 bg-body-tertiary"
                        >
                            <div
                                class="d-flex align-items-center gap-2 mb-1"
                            >
                                {#if pengumpulan.terlambat}
                                    <Badge color="warning" pill
                                        >Terlambat</Badge
                                    >
                                {:else}
                                    <Badge color="success" pill
                                        >Tepat Waktu</Badge
                                    >
                                {/if}
                                <span class="text-sm text-muted"
                                    >Dikumpulkan {pengumpulan.submitted_at}</span
                                >
                            </div>
                            {#if pengumpulan.nilai !== null}
                                <div
                                    class="d-flex align-items-center gap-2 mt-2"
                                >
                                    <Badge color="success" pill>
                                        <i
                                            class="bi bi-check2-circle me-1"
                                        ></i>
                                        Nilai: {pengumpulan.nilai}/{tugas.poin}
                                    </Badge>
                                </div>
                            {:else}
                                <div class="text-muted small mt-2">
                                    <i class="bi bi-hourglass me-1"></i>
                                    Belum dinilai — tunggu guru menilai
                                    jawabanmu.
                                </div>
                            {/if}
                            <div
                                class="small d-flex align-items-center gap-2"
                            >
                                <i
                                    class="bi bi-file-earmark-check text-success"
                                ></i>
                                <span class="text-truncate"
                                    >{pengumpulan.file_name ??
                                        'Jawaban teks'}</span
                                >
                            </div>
                            {#if pengumpulan.jawaban_teks}
                                <div class="small mt-2">
                                    <div class="text-muted mb-1">
                                        <i
                                            class="bi bi-pencil-square me-1"
                                        ></i>Jawaban:
                                    </div>
                                    <p
                                        class="mb-0 p-2 border rounded-1 text-pre-wrap"
                                        style="background-color: var(--app-shell-surface);"
                                    >
                                        {pengumpulan.jawaban_teks}
                                    </p>
                                </div>
                            {/if}
                        </div>
                    {/if}

                    {#if deadlineLewat && !pengumpulan}
                        <Alert color="danger" class="mb-0">
                            <i class="bi bi-x-circle me-2"></i>
                            Batas waktu sudah lewat, tugas tidak bisa dikumpulkan
                            lagi.
                        </Alert>
                    {:else}
                        <div class="form-label text-sm">
                            {pengumpulan
                                ? 'Ganti Jawaban'
                                : 'Kumpulkan Jawaban'}
                        </div>

                        {#if tugas.jenis_pengumpulan !== 'teks'}
                            <label
                                for="kumpul-file"
                                class="form-label text-sm text-muted fw-normal"
                            >
                                Berkas Jawaban
                                {tugas.jenis_pengumpulan ===
                                'keduanya'
                                    ? '(opsional jika menulis teks)'
                                    : ''}
                            </label>
                            <input
                                id="kumpul-file"
                                type="file"
                                class="form-control {form.errors
                                    .file || fileError
                                    ? 'is-invalid'
                                    : ''}"
                                accept={ACCEPT_ATTRIBUTE}
                                onchange={(e) =>
                                    setFile(
                                        (
                                            e.currentTarget as HTMLInputElement
                                        ).files?.[0] ?? null,
                                    )}
                            />
                            {#if form.file}
                                <div
                                    class="form-text d-flex align-items-center gap-2"
                                >
                                    <i
                                        class="bi bi-file-earmark-arrow-up text-primary"
                                    ></i>
                                    <span class="text-truncate"
                                        >{form.file.name} ({formatBytes(
                                            form.file.size,
                                        )})</span
                                    >
                                </div>
                            {:else if !pengumpulan?.file_name}
                                <div class="form-text mb-3">
                                    PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX,
                                    JPG, PNG, ZIP, MP4, MP3, TXT —
                                    maks. 20 MB
                                </div>
                            {:else}
                                <div
                                    class="form-text text-xs d-flex align-items-center gap-2 mb-3"
                                >
                                    <i
                                        class="bi bi-file-earmark-check text-success"
                                    ></i>
                                    <span class="text-truncate"
                                        >Berkas saat ini:
                                        {pengumpulan.file_name}</span
                                    >
                                </div>
                            {/if}
                            {#if fileError}
                                <div class="invalid-feedback d-block">
                                    {fileError}
                                </div>
                            {:else if form.errors.file}
                                <div class="invalid-feedback d-block">
                                    {form.errors.file}
                                </div>
                            {/if}
                        {/if}

                        {#if tugas.jenis_pengumpulan !== 'file'}
                            <label
                                for="kumpul-teks"
                                class="form-label small text-muted fw-normal mt-3"
                            >
                                Tulis Jawaban
                                {tugas.jenis_pengumpulan ===
                                'keduanya'
                                    ? '(opsional jika mengunggah berkas)'
                                    : ''}
                            </label>
                            <textarea
                                id="kumpul-teks"
                                rows="5"
                                class="form-control {form.errors
                                    .jawaban_teks || teksError
                                    ? 'is-invalid'
                                    : ''}"
                                placeholder="Tulis jawabanmu di sini…"
                                value={form.jawaban_teks}
                                oninput={(e) => {
                                    form.jawaban_teks = (
                                        e.currentTarget as HTMLTextAreaElement
                                    ).value;
                                    teksError = '';
                                }}
                            ></textarea>
                            {#if teksError}
                                <div class="invalid-feedback d-block">
                                    {teksError}
                                </div>
                            {:else if form.errors.jawaban_teks}
                                <div class="invalid-feedback d-block">
                                    {form.errors.jawaban_teks}
                                </div>
                            {/if}
                        {/if}
                        <Button
                            color="primary"
                            class="mt-3 w-100 d-flex align-items-center justify-content-center"
                            onclick={kumpulkan}
                            disabled={form.processing}
                        >
                            {#if form.processing}
                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                            {:else}
                                <i class="bi bi-send me-1"></i>
                            {/if}
                            {pengumpulan
                                ? 'Perbarui Jawaban'
                                : 'Kumpulkan Tugas'}
                        </Button>
                    {/if}
                </CardBody>
            </Card>
        </div>
    </div>
</div>

<style>












    .section-title {
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: capitalize;
        color: var(--bs-secondary);
    }

    .lampiran {
        max-width: 100%;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    .lampiran:hover {
        background: var(--bs-primary-bg-subtle);
        border-color: var(--bs-primary-border-subtle);
    }

    :global(.rich-deskripsi p) {
        margin-bottom: 0.5rem;
    }

    :global(.rich-deskripsi p:last-child) {
        margin-bottom: 0;
    }

    :global(.rich-deskripsi ul),
    :global(.rich-deskripsi ol) {
        margin-bottom: 0.5rem;
        padding-left: 1.25rem;
    }

    :global(.rich-deskripsi h1),
    :global(.rich-deskripsi h2),
    :global(.rich-deskripsi h3),
    :global(.rich-deskripsi h4) {
        margin: 1.5rem 0 0.5rem;
        scroll-margin-top: 6rem;
    }

    :global(.rich-deskripsi h1:first-child),
    :global(.rich-deskripsi h2:first-child),
    :global(.rich-deskripsi h3:first-child) {
        margin-top: 0;
    }

    :global(.rich-deskripsi a) {
        color: var(--bs-primary);
    }

    :global(.rich-deskripsi iframe) {
        max-width: 100%;
        border: 0;
        border-radius: var(--bs-border-radius);
    }

    .text-pre-wrap {
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Header bersih tanpa gradient */
    .tugas-header {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.75rem;
        padding: 1.25rem;
    }
</style>
