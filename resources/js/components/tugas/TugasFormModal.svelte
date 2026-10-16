<script lang="ts">
    import {
        Button,
        Modal,
        ModalBody,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import RichTextEditor from '@/components/RichTextEditor.svelte';
    import Select from '@/components/Select.svelte';
    import VanillaDatePicker from '@/components/DatePicker/VanillaDatePicker.svelte';
    import { formatBytes } from '@/lib/materi';
    import { extractId } from '@/lib/utils';
    import {
        ACCEPT_ATTRIBUTE,
        PENGGUMPULAN_INFO,
        fileIcon,
        validasiFile,
    } from '@/lib/tugas';
    import type { TugasFormState } from '@/lib/tugas';

    let {
        mode,
        open,
        form,
        penugasan,
        existingFileName = null,
        existingFileSize = 0,
        onClose,
        onSubmit,
    }: {
        mode: 'buat' | 'edit';
        open: boolean;
        form: TugasFormState;
        penugasan: { value: number; label: string }[];
        existingFileName?: string | null;
        existingFileSize?: number;
        onClose: () => void;
        onSubmit: () => void;
    } = $props();

    let fileError = $state('');

    // svelte-ignore state_referenced_locally
    const isBuat = mode === 'buat';
    const idPrefix = isBuat ? 'tugas' : 'edit-tugas';

    function onFileChange(file: File | null) {
        const error = validasiFile(file);
        fileError = error ?? '';
        form.file = error ? null : file;
    }
</script>

<Modal isOpen={open} toggle={onClose} scrollable size="lg">
    <ModalHeader toggle={onClose}>
        {#if isBuat}
            <i class="bi bi-plus-circle me-2 text-primary"></i>Buat Tugas
        {:else}
            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Tugas
        {/if}
    </ModalHeader>
    <ModalBody>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="{idPrefix}-kelas" class="form-label text-xs"
                    >Kelas &amp; Mata Pelajaran</label
                >
                <Select
                    id="{idPrefix}-kelas"
                    items={penugasan}
                    value={form.guru_kelas_id}
                    placeholder="Pilih kelas &amp; mata pelajaran…"
                    getOptionValue={(item) => item.value}
                    onchange={(v) => (form.guru_kelas_id = extractId(v))}
                    hasError={Boolean(form.errors.guru_kelas_id)}
                />
                {#if form.errors.guru_kelas_id}
                    <div class="invalid-feedback d-block">
                        {form.errors.guru_kelas_id}
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <label for="{idPrefix}-judul" class="form-label text-xs"
                    >Judul Tugas</label
                >
                <input
                    id="{idPrefix}-judul"
                    type="text"
                    class="form-control {form.errors.judul
                        ? 'is-invalid'
                        : ''}"
                    placeholder="Contoh: Latihan Soal Bab 1"
                    value={form.judul}
                    oninput={(e) =>
                        (form.judul = (
                            e.currentTarget as HTMLInputElement
                        ).value)}
                />
                {#if form.errors.judul}
                    <div class="invalid-feedback">
                        {form.errors.judul}
                    </div>
                {/if}
            </div>
            <div class="col-12">
                <label for="{idPrefix}-deskripsi" class="form-label text-xs"
                    >Deskripsi <span class="text-muted fw-normal"
                        >(opsional)</span
                    ></label
                >
                <RichTextEditor
                    id="{idPrefix}-deskripsi"
                    value={form.deskripsi}
                    placeholder="Jelaskan tugasnya, misalnya petunjuk pengerjaan…"
                    invalid={Boolean(form.errors.deskripsi)}
                    onchange={(html) => (form.deskripsi = html)}
                />
                {#if form.errors.deskripsi}
                    <div class="invalid-feedback d-block">
                        {form.errors.deskripsi}
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <VanillaDatePicker
                    label={isBuat
                        ? 'Tanggal Terbit (kosongkan = langsung terbit)'
                        : 'Tanggal Terbit'}
                    placeholder="Pilih tanggal terbit…"
                    value={form.tanggal_terbit || null}
                    onchange={(v) => (form.tanggal_terbit = v ?? '')}
                />
                {#if form.errors.tanggal_terbit}
                    <div class="invalid-feedback d-block">
                        {form.errors.tanggal_terbit}
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <VanillaDatePicker
                    label="Batas Waktu Pengumpulan"
                    placeholder="Pilih batas waktu…"
                    value={form.deadline || null}
                    dateMin={form.tanggal_terbit
                        ? form.tanggal_terbit.slice(0, 10)
                        : null}
                    onchange={(v) => (form.deadline = v ?? '')}
                />
                {#if form.errors.deadline}
                    <div class="invalid-feedback d-block">
                        {form.errors.deadline}
                    </div>
                {/if}
            </div>
            <div class="col-12">
                <!-- svelte-ignore a11y_label_has_associated_control -->
                <label class="form-label text-xs d-block mb-1"
                    >Cara Pengumpulan</label
                >
                <div class="d-flex flex-wrap gap-3">
                    {#each Object.entries(PENGGUMPULAN_INFO) as [jenis, info] (jenis)}
                        <label class="form-check form-check-inline mb-0">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="{idPrefix}-jenis-pengumpulan"
                                value={jenis}
                                checked={form.jenis_pengumpulan === jenis}
                                onchange={() =>
                                    (form.jenis_pengumpulan =
                                        jenis as TugasFormState['jenis_pengumpulan'])}
                            />
                            <span class="form-check-label"
                                ><i class="bi {info.icon} me-1"></i
                                >{info.label}</span
                            >
                        </label>
                    {/each}
                </div>
                {#if form.errors.jenis_pengumpulan}
                    <div class="invalid-feedback d-block">
                        {form.errors.jenis_pengumpulan}
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <label for="{idPrefix}-poin" class="form-label text-xs"
                    >Poin Maksimal</label
                >
                <input
                    id="{idPrefix}-poin"
                    type="number"
                    min="1"
                    max="1000"
                    class="form-control {form.errors.poin
                        ? 'is-invalid'
                        : ''}"
                    value={form.poin}
                    oninput={(e) =>
                        (form.poin = Number(
                            (e.currentTarget as HTMLInputElement).value,
                        ))}
                />
                {#if form.errors.poin}
                    <div class="invalid-feedback">
                        {form.errors.poin}
                    </div>
                {/if}
            </div>
            <div class="col-12">
                <label for="{idPrefix}-file" class="form-label text-xs"
                    >Berkas Tugas <span class="text-muted fw-normal"
                        >(opsional — lampiran soal)</span
                    ></label
                >
                <input
                    id="{idPrefix}-file"
                    type="file"
                    class="form-control {form.errors.file || fileError
                        ? 'is-invalid'
                        : ''}"
                    accept={ACCEPT_ATTRIBUTE}
                    onchange={(e) =>
                        onFileChange(
                            (e.currentTarget as HTMLInputElement)
                                .files?.[0] ?? null,
                        )}
                />
                {#if form.file}
                    <div
                        class="form-text d-flex align-items-center gap-2"
                    >
                        <i
                            class={`bi ${fileIcon(form.file.name).icon} ${fileIcon(form.file.name).color}`}
                        ></i>
                        <span class="text-truncate"
                            >{isBuat ? '' : 'Berkas baru: '}{form.file.name} ({formatBytes(
                                form.file.size,
                            )})</span
                        >
                    </div>
                {:else if !isBuat && existingFileName}
                    <div
                        class="form-text d-flex align-items-center gap-2"
                    >
                        <i
                            class={`bi ${fileIcon(existingFileName).icon} ${fileIcon(existingFileName).color}`}
                        ></i>
                        <span class="text-truncate"
                            >Berkas saat ini: {existingFileName} ({formatBytes(
                                existingFileSize,
                            )})</span
                        >
                    </div>
                    <div class="form-text">
                        Kosongkan kolom ini untuk tetap memakai berkas
                        yang sama.
                    </div>
                {:else}
                    <div class="form-text">
                        PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, JPG, PNG, ZIP,
                        MP4, MP3, TXT — maks. 20 MB
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
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <Button
                    color="outline-secondary"
                    onclick={onClose}
                    disabled={form.processing}
                >
                    Batal
                </Button>
                <Button
                    color="primary"
                    onclick={onSubmit}
                    disabled={form.processing}
                >
                    {#if form.processing}
                        <span
                            class="spinner-border spinner-border-sm me-1"
                        ></span>
                    {:else}
                        <i class="bi bi-check-lg me-1"></i>
                    {/if}
                    {isBuat ? 'Terbitkan Tugas' : 'Simpan Perubahan'}
                </Button>
            </div>
        </div>
    </ModalBody>
</Modal>
