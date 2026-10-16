<script lang="ts">
    import { router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import TugasFormModal from '@/components/tugas/TugasFormModal.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import { stripHtml } from '@/lib/materi';
    import { extractId } from '@/lib/utils';
    import {
        PENGGUMPULAN_INFO,
        deadlineLewat,
        fileIcon,
    } from '@/lib/tugas';
    import type { JenisPengumpulan } from '@/lib/tugas';
    import TugasController from '@/actions/App/Http/Controllers/Guru/TugasController';
    import type { PaginationMeta, PenugasanOption } from '@/types/models';

    type TugasItem = {
        id: number;
        judul: string;
        deskripsi: string | null;
        file_name: string | null;
        file_size: number;
        jenis_pengumpulan: JenisPengumpulan;
        kelas: string | null;
        matpel: string | null;
        tanggal_terbit: string | null;
        deadline: string | null;
        deadline_at: string | null;
        sudah_terbit: boolean;
        jumlah_siswa: number;
        jumlah_kumpul: number;
        jumlah_terlambat: number;
        dibuat_pada: string;
    };
    type EditTugas = {
        id: number;
        guru_kelas_id: number | null;
        judul: string;
        deskripsi: string | null;
        tanggal_terbit: string | null;
        deadline: string | null;
        jenis_pengumpulan: JenisPengumpulan;
        poin: number;
        file_name: string | null;
        file_size: number;
    };

    let {
        tugases,
        penugasan = [],
        filters = { guru_kelas_id: null, q: '' },
        editTugas = null,
    }: {
        tugases: PaginationMeta & { data: TugasItem[] };
        penugasan: PenugasanOption[];
        filters: { guru_kelas_id: number | null; q: string };
        editTugas: EditTugas | null;
    } = $props();

    const form = useForm({
        guru_kelas_id: null as number | null,
        judul: '',
        deskripsi: '',
        tanggal_terbit: '',
        deadline: '',
        jenis_pengumpulan: 'file' as JenisPengumpulan,
        poin: 100,
        file: null as File | null,
    });

    const editForm = useForm({
        guru_kelas_id: null as number | null,
        judul: '',
        deskripsi: '',
        tanggal_terbit: '',
        deadline: '',
        jenis_pengumpulan: 'file' as JenisPengumpulan,
        poin: 100,
        file: null as File | null,
    });

    // svelte-ignore state_referenced_locally
    let filterGuruKelasId: number | null = $state(
        filters.guru_kelas_id,
    );
    // svelte-ignore state_referenced_locally
    let searchInput: string = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let modalOpen = $state(false);

    let editOpen = $state(false);

    $effect(() => {
        if (!editTugas) {
            return;
        }
        editForm.guru_kelas_id = editTugas.guru_kelas_id;
        editForm.judul = editTugas.judul;
        editForm.deskripsi = editTugas.deskripsi ?? '';
        editForm.tanggal_terbit = editTugas.tanggal_terbit ?? '';
        editForm.deadline = editTugas.deadline ?? '';
        editForm.jenis_pengumpulan =
            editTugas.jenis_pengumpulan ?? 'file';
        editForm.poin = editTugas.poin ?? 100;
        editForm.file = null;
    });

    function buatTugas() {
        if (!form.guru_kelas_id) {
            form.setError(
                'guru_kelas_id',
                'Pilih kelas & mata pelajaran dulu.',
            );
            return;
        }
        form.post(TugasController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                modalOpen = false;
            },
        });
    }

    function bukaEdit(item: TugasItem) {
        router.get(
            TugasController.edit({ tugas: item.id }).url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['editTugas'],
                onSuccess: () => {
                    editOpen = true;
                },
            },
        );
    }

    function tutupEdit() {
        editOpen = false;
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['editTugas'],
                onSuccess: () => {
                    editForm.clearErrors();
                },
            },
        );
    }

    function simpanEdit() {
        if (!editTugas) {
            return;
        }
        editForm.put(
            TugasController.update({ tugas: editTugas.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    editOpen = false;
                    editForm.reset();
                },
            },
        );
    }

    async function hapusTugas(item: TugasItem) {
        const ok = await confirm.show({
            title: 'Hapus Tugas',
            message: `Tugas "${item.judul}" beserta semua pengumpulan siswanya akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(
            TugasController.destroy({ tugas: item.id }).url,
            {
                preserveScroll: true,
            },
        );
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                guru_kelas_id: filterGuruKelasId ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['tugases'],
            },
        );
    }

    function onSearchInput() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 400);
    }

    function goToPage(page: number) {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                page,
                guru_kelas_id: filterGuruKelasId ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['tugases'],
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Tugas"
        subtitle="Buat tugas, pantau pengumpulan, dan unduh jawaban siswa."
    >
        {#snippet actions()}
            <Button
                color="primary"
                onclick={() => (modalOpen = true)}
            >
                <i class="bi bi-plus-lg me-1"></i>Buat Tugas
            </Button>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div
                class="d-flex flex-wrap align-items-center gap-2 mb-3"
            >
                <div style="min-width: 240px">
                    <Select
                        id="filter-penugasan"
                        items={penugasan}
                        value={filterGuruKelasId}
                        placeholder="Semua kelas"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterGuruKelasId = extractId(v);
                            reload();
                        }}
                    />
                </div>
                <div
                    class="input-group input-group-sm"
                    style="max-width: 280px"
                >
                    <span class="input-group-text bg-body"
                        ><i class="bi bi-search"></i></span
                    >
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Cari judul tugas…"
                        value={searchInput}
                        oninput={(e) =>
                            (searchInput = (
                                e.currentTarget as HTMLInputElement
                            ).value)}
                        onkeyup={onSearchInput}
                    />
                </div>
            </div>

            {#if tugases.data.length === 0}
                <div class="text-center text-muted py-5">
                    <i
                        class="bi bi-clipboard-x display-5 d-block mb-2"
                    ></i>
                    <div>
                        Belum ada tugas. Klik "Buat Tugas" untuk
                        mulai.
                    </div>
                </div>
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kelas &amp; Matpel</th>
                                <th>Terbit</th>
                                <th>Batas Waktu</th>
                                <th>Pengumpulan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each tugases.data as item (item.id)}
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {item.judul}
                                        </div>
                                        {#if item.deskripsi}
                                            <div
                                                class="text-muted small text-truncate"
                                                style="max-width: 320px"
                                            >
                                                {stripHtml(
                                                    item.deskripsi,
                                                )}
                                            </div>
                                        {/if}
                                        {#if item.file_name}
                                            <div
                                                class="small text-muted d-flex align-items-center gap-1"
                                            >
                                                <i
                                                    class={`bi ${fileIcon(item.file_name).icon} ${fileIcon(item.file_name).color}`}
                                                ></i>
                                                <span
                                                    class="text-truncate"
                                                    >{item.file_name}</span
                                                >
                                            </div>
                                        {/if}
                                    </td>
                                    <td class="text-nowrap">
                                        {item.kelas ?? 'Kelas'}
                                        <span class="text-muted"
                                            >·</span
                                        >
                                        {item.matpel ?? 'Matpel'}
                                    </td>
                                    <td class="text-nowrap small">
                                        {#if item.sudah_terbit}
                                            <Badge
                                                color="success"
                                                pill>Terbit</Badge
                                            >
                                            <div class="text-muted">
                                                {item.tanggal_terbit}
                                            </div>
                                        {:else}
                                            <Badge
                                                color="warning"
                                                pill>Terjadwal</Badge
                                            >
                                            <div class="text-muted">
                                                {item.tanggal_terbit}
                                            </div>
                                        {/if}
                                    </td>
                                    <td class="text-nowrap small">
                                        <span
                                            class={deadlineLewat(item.deadline_at)
                                                ? 'text-danger fw-semibold'
                                                : ''}
                                        >
                                            <i
                                                class="bi bi-hourglass-split me-1"
                                            ></i>{item.deadline}
                                        </span>
                                        {#if deadlineLewat(item.deadline_at)}
                                            <div class="text-muted">
                                                Sudah lewat
                                            </div>
                                        {/if}
                                    </td>
                                    <td class="text-nowrap">
                                        <div
                                            class="small text-muted d-flex align-items-center gap-1 mb-1"
                                        >
                                            <i
                                                class={`bi ${PENGGUMPULAN_INFO[item.jenis_pengumpulan].icon}`}
                                            ></i>
                                            {PENGGUMPULAN_INFO[
                                                item.jenis_pengumpulan
                                            ].label}
                                        </div>
                                        {#if item.jumlah_siswa > 0}
                                            <span class="fw-semibold"
                                                >{item.jumlah_kumpul}/{item.jumlah_siswa}</span
                                            >
                                            {#if item.jumlah_terlambat > 0}
                                                <Badge
                                                    color="warning"
                                                    pill
                                                    class="ms-1"
                                                >
                                                    {item.jumlah_terlambat}
                                                    terlambat
                                                </Badge>
                                            {/if}
                                            {#if item.jumlah_kumpul === item.jumlah_siswa && item.jumlah_kumpul > 0}
                                                <Badge
                                                    color="success"
                                                    pill
                                                    class="ms-1"
                                                    >Lengkap</Badge
                                                >
                                            {/if}
                                        {:else}
                                            <span class="text-muted"
                                                >—</span
                                            >
                                        {/if}
                                    </td>
                                    <td class="text-end">
                                        <div
                                            class="d-inline-flex gap-1"
                                        >
                                            <Button
                                                size="sm"
                                                color="outline-primary"
                                                onclick={() =>
                                                    router.visit(
                                                        TugasController.pengumpulan(
                                                            {
                                                                tugas: item.id,
                                                            },
                                                        ).url,
                                                    )}
                                                title="Lihat pengumpulan"
                                            >
                                                <i
                                                    class="bi bi-people"
                                                ></i>
                                            </Button>
                                            {#if item.file_name}
                                                <a
                                                    href={TugasController.unduh(
                                                        {
                                                            tugas: item.id,
                                                        },
                                                    ).url}
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Unduh berkas tugas"
                                                >
                                                    <i
                                                        class="bi bi-download"
                                                    ></i>
                                                </a>
                                            {/if}
                                            <Button
                                                size="sm"
                                                color="outline-secondary"
                                                onclick={() =>
                                                    bukaEdit(item)}
                                                title="Edit tugas"
                                            >
                                                <i
                                                    class="bi bi-pencil"
                                                ></i>
                                            </Button>
                                            <Button
                                                size="sm"
                                                color="outline-danger"
                                                onclick={() =>
                                                    hapusTugas(item)}
                                                title="Hapus tugas"
                                            >
                                                <i class="bi bi-trash"
                                                ></i>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={tugases} onPageChange={goToPage} />
            {/if}
        </CardBody>
    </Card>


    <TugasFormModal
        mode="buat"
        open={modalOpen}
        {form}
        {penugasan}
        onClose={() => (modalOpen = false)}
        onSubmit={buatTugas}
    />

    <TugasFormModal
        mode="edit"
        open={editOpen}
        form={editForm}
        {penugasan}
        existingFileName={editTugas?.file_name ?? null}
        existingFileSize={editTugas?.file_size ?? 0}
        onClose={tutupEdit}
        onSubmit={simpanEdit}
    />
</div>