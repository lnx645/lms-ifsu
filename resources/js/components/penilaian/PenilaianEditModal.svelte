<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import {
        Button,
        Modal,
        ModalBody,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import type { PenilaianItem } from '@/types/models';

    let {
        open,
        penilaian,
        onClose,
    }: {
        open: boolean;
        penilaian: PenilaianItem | null;
        onClose: () => void;
    } = $props();

    const form = useForm({
        nama: '',
        deskripsi: '',
        tipe: '',
        nilai_maks: 0,
        bobot: 0,
        aktif: false,
    });

    let prevOpen = $state(false);

    $effect(() => {
        if (open && !prevOpen && penilaian) {
            form.nama = penilaian.nama;
            form.deskripsi = penilaian.deskripsi ?? '';
            form.tipe = penilaian.tipe;
            form.nilai_maks = penilaian.nilai_maks;
            form.bobot = penilaian.bobot;
            form.aktif = penilaian.aktif;
            form.clearErrors();
        }
        prevOpen = open;
    });

    const dariTugas = () => penilaian?.sumber === 'tugas';

    function handleSubmit() {
        if (!penilaian) {
            return;
        }
        form.put(PenilaianController.update({ penilaian: penilaian.id }).url, {
            onSuccess: onClose,
        });
    }
</script>

<Modal isOpen={open} toggle={onClose} scrollable>
    <ModalHeader toggle={onClose}>
        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Penilaian
    </ModalHeader>
    <ModalBody>
        <div class="row g-3">
            <div class="col-12">
                <label for="edit-penilaian-nama" class="form-label text-xs"
                    >Nama Penilaian</label
                >
                <input
                    id="edit-penilaian-nama"
                    type="text"
                    class="form-control {form.errors.nama ? 'is-invalid' : ''}"
                    placeholder="Contoh: Ulangan Harian, PTS, PAS"
                    value={form.nama}
                    oninput={(e) =>
                        (form.nama = (e.currentTarget as HTMLInputElement).value)}
                />
                {#if form.errors.nama}
                    <div class="invalid-feedback">{form.errors.nama}</div>
                {/if}
            </div>
            <div class="col-12">
                <label for="edit-penilaian-deskripsi" class="form-label text-xs"
                    >Deskripsi <span class="text-muted fw-normal">(opsional)</span></label
                >
                <input
                    id="edit-penilaian-deskripsi"
                    type="text"
                    class="form-control {form.errors.deskripsi ? 'is-invalid' : ''}"
                    placeholder="Misal: Penilaian tengah semester ganjil"
                    value={form.deskripsi}
                    oninput={(e) =>
                        (form.deskripsi = (e.currentTarget as HTMLInputElement).value)}
                />
                {#if form.errors.deskripsi}
                    <div class="invalid-feedback">{form.errors.deskripsi}</div>
                {/if}
            </div>
            <div class="col-md-6">
                <label for="edit-penilaian-tipe" class="form-label text-xs">Tipe</label>
                <select
                    id="edit-penilaian-tipe"
                    class="form-select {form.errors.tipe ? 'is-invalid' : ''}"
                    value={form.tipe}
                    disabled={dariTugas()}
                    onchange={(e) =>
                        (form.tipe = (e.currentTarget as HTMLSelectElement).value)}
                >
                    <option value="" disabled>Pilih tipe</option>
                    <option value="kognitif">Kognitif</option>
                    <option value="sikap">Sikap</option>
                    <option value="tugas">Tugas</option>
                    <option value="cbt">CBT</option>
                </select>
                {#if dariTugas()}
                    <div class="form-text">
                        Penilaian dari tugas guru — tipe tidak bisa diubah.
                    </div>
                {/if}
                {#if form.errors.tipe}
                    <div class="invalid-feedback">{form.errors.tipe}</div>
                {/if}
            </div>
            <div class="col-md-6">
                <label for="edit-penilaian-nilai-maks" class="form-label text-xs"
                    >Nilai Maksimum</label
                >
                <input
                    id="edit-penilaian-nilai-maks"
                    type="number"
                    min="0"
                    class="form-control {form.errors.nilai_maks ? 'is-invalid' : ''}"
                    placeholder="Contoh: 100"
                    value={form.nilai_maks}
                    disabled={dariTugas()}
                    oninput={(e) =>
                        (form.nilai_maks = Number(
                            (e.currentTarget as HTMLInputElement).value,
                        ))}
                />
                {#if form.errors.nilai_maks}
                    <div class="invalid-feedback">{form.errors.nilai_maks}</div>
                {/if}
            </div>
            <div class="col-md-6">
                <label for="edit-penilaian-bobot" class="form-label text-xs"
                    >Bobot (%)</label
                >
                <input
                    id="edit-penilaian-bobot"
                    type="number"
                    min="0"
                    max="100"
                    class="form-control {form.errors.bobot ? 'is-invalid' : ''}"
                    placeholder="0"
                    value={form.bobot}
                    oninput={(e) =>
                        (form.bobot = Number(
                            (e.currentTarget as HTMLInputElement).value,
                        ))}
                />
                {#if form.errors.bobot}
                    <div class="invalid-feedback">{form.errors.bobot}</div>
                {/if}
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input
                        id="edit-penilaian-aktif"
                        class="form-check-input"
                        type="checkbox"
                        checked={form.aktif}
                        onchange={(e) =>
                            (form.aktif = (e.currentTarget as HTMLInputElement).checked)}
                    />
                    <label for="edit-penilaian-aktif" class="form-check-label text-xs"
                        >Aktif — tampil di menu guru</label
                    >
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <Button color="outline-secondary" onclick={onClose} disabled={form.processing}>
                    Batal
                </Button>
                <Button color="primary" onclick={handleSubmit} disabled={form.processing}>
                    {#if form.processing}
                        <span class="spinner-border spinner-border-sm me-1"></span>
                    {:else}
                        <i class="bi bi-check-lg me-1"></i>
                    {/if}
                    Simpan Perubahan
                </Button>
            </div>
        </div>
    </ModalBody>
</Modal>
