<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        CardHeader,
        Dropdown,
        DropdownItem,
        DropdownMenu,
        DropdownToggle,
        Input,
        Label,
        Modal,
        ModalBody,
        ModalFooter,
        ModalHeader,
        Table,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import UjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import AkademikController from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';

    function formatTanggal(iso: string | null): string {
        if (!iso) return '–';
        return new Date(iso.replace(' ', 'T')).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    type SesiItem = {
        id: number;
        nama: string;
        urutan: number;
        waktu_mulai: string;
        waktu_selesai: string;
        kapasitas: number | null;
        ruang: { id: number; nama: string } | null;
        sedang_aktif: boolean;
        sudah_lewat: boolean;
        jumlah_peserta: number;
        jumlah_terisi: number;
        bisa_hapus: boolean;
    };

    type SiswaItem = { nisn: string; nama: string };
    type RuangItem = { id: number; kode: string; nama: string };

    let {
        ujian,
        sesis,
        siswaKelas,
        pesertaMap,
        ruangs,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: string;
            kelas: string | null;
            matpel: string | null;
            tanggal_mulai: string | null;
            tanggal_selesai: string | null;
            durasi_menit: number;
            mode_sesi: 'bebas' | 'terjadwal';
        };
        sesis: SesiItem[];
        siswaKelas: SiswaItem[];
        pesertaMap: Record<number, string[]>;
        ruangs: RuangItem[];
    } = $props();

    // svelte-ignore state_referenced_locally
    let modeSesi = $state(ujian.mode_sesi);
    let modalSesiOpen = $state(false);
    let editSesi = $state<SesiItem | null>(null);
    // svelte-ignore state_referenced_locally
    let pilihSesiId = $state<number | null>(sesis[0]?.id ?? null);
    let nisnDipilih = $state<string[]>([]);
    function aturRuang(s: SesiItem, ruangId: string) {
        router.put(
            AkademikController.sesiRuang({ ujian: ujian.id, sesi: s.id }).url,
            { ruang_ujian_id: ruangId ? Number(ruangId) : null },
            { preserveScroll: true },
        );
    }

    function baseSesiForm() {
        return { nama: '', waktu_mulai: '', waktu_selesai: '', kapasitas: null as number | null };
    }
    const sesiForm = useForm(baseSesiForm());

    function bukaTambah() {
        editSesi = null;
        sesiForm.reset();
        // saran nama otomatis: Sesi N+1
        const n = sesis.length + 1;
        sesiForm.nama = `Sesi ${n}`;
        modalSesiOpen = true;
    }

    function bukaEdit(s: SesiItem) {
        editSesi = s;
        sesiForm.nama = s.nama;
        sesiForm.waktu_mulai = s.waktu_mulai;
        sesiForm.waktu_selesai = s.waktu_selesai;
        sesiForm.kapasitas = s.kapasitas;
        modalSesiOpen = true;
    }

    function simpanSesi() {
        if (editSesi) {
            sesiForm.put(
                UjianController.sesiUpdate({ ujian: ujian.id, sesi: editSesi.id }).url,
                { onSuccess: () => (modalSesiOpen = false), preserveScroll: true },
            );
        } else {
            sesiForm.post(UjianController.sesiStore({ ujian: ujian.id }).url, {
                onSuccess: () => (modalSesiOpen = false),
                preserveScroll: true,
            });
        }
    }

    async function hapusSesi(s: SesiItem) {
        const ok = await confirm.show({
            title: `Hapus ${s.nama}`,
            message: `Sesi "${s.nama}" akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(UjianController.sesiDestroy({ ujian: ujian.id, sesi: s.id }).url, {
            preserveScroll: true,
        });
    }

    function ubahMode() {
        router.put(
            UjianController.sesiMode({ ujian: ujian.id }).url,
            { mode_sesi: modeSesi },
            { preserveScroll: true },
        );
    }

    const sesiAktif = $derived(sesis.find((s) => s.id === pilihSesiId) ?? null);
    const nisnTerjadwal = $derived(new Set(Object.values(pesertaMap).flat()));
    const siswaBelumTerjadwal = $derived(
        siswaKelas.filter((s) => !nisnTerjadwal.has(s.nisn)),
    );
    const pesertaSesiAktif = $derived(
        (pesertaMap[sesiAktif?.id ?? 0] ?? []).map(
            (nisn) => siswaKelas.find((s) => s.nisn === nisn) ?? { nisn, nama: nisn },
        ),
    );

    function toggleNisn(nisn: string) {
        nisnDipilih = nisnDipilih.includes(nisn)
            ? nisnDipilih.filter((n) => n !== nisn)
            : [...nisnDipilih, nisn];
    }

    function jadwalkan() {
        if (!sesiAktif || nisnDipilih.length === 0) return;
        router.post(
            UjianController.sesiPesertaStore({ ujian: ujian.id, sesi: sesiAktif.id }).url,
            { nisn: nisnDipilih },
            { preserveScroll: true, onSuccess: () => (nisnDipilih = []) },
        );
    }

    async function batalkanJadwal(nisn: string) {
        if (!sesiAktif) return;
        const ok = await confirm.show({
            title: 'Batalkan penjadwalan',
            message: `Batalkan penjadwalan siswa ${nisn} dari ${sesiAktif.nama}?`,
            confirmText: 'Ya, Batalkan',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(
            UjianController.sesiPesertaDestroy({
                ujian: ujian.id,
                sesi: sesiAktif.id,
                nisn,
            }).url,
            { preserveScroll: true },
        );
    }

    function statusBadge(s: SesiItem) {
        if (s.sedang_aktif) return { color: 'success', label: 'Berlangsung' };
        if (s.sudah_lewat) return { color: 'secondary', label: 'Selesai' };
        return { color: 'warning', label: 'Terjadwal' };
    }
</script>

<PageHeader
    title="Sesi Ujian"
    subtitle="{ujian.judul} · {ujian.kelas ?? '-'} · {ujian.matpel ?? '-'}"
/>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <a href={UjianController.index().url} class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <div class="d-flex align-items-center gap-2 ms-auto">
        <Label class="mb-0 small text-muted" for="modeSesi">Mode sesi:</Label>
        <Input
            id="modeSesi"
            type="select"
            bsSize="sm"
            bind:value={modeSesi}
            onchange={ubahMode}
            style="width:auto"
        >
            <option value="bebas">Bebas (rebutan)</option>
            <option value="terjadwal">Terjadwal (ala USBK)</option>
        </Input>
    </div>
    <Button color="primary" size="sm" onclick={bukaTambah}>
        <i class="bi bi-plus-lg me-1"></i> Tambah Sesi
    </Button>
</div>

{#if ujian.mode_sesi === 'terjadwal'}
    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        Mode <strong>terjadwal</strong>: siswa hanya bisa mengerjakan pada sesi yang
        dijatahkan untuknya, dan pengerjaan otomatis berakhir saat sesi selesai.
    </div>
{:else}
    <div class="alert alert-secondary py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        Mode <strong>bebas</strong>: siswa boleh masuk sesi aktif mana saja
        (kapasitas first-come).
    </div>
{/if}

<Card class="mb-3">
    <CardHeader>Daftar Sesi</CardHeader>
    <CardBody class="p-0">
        {#if sesis.length === 0}
            <EmptyState
                message="Belum ada sesi"
                hint="Tambahkan Sesi 1, Sesi 2, dst. mengikuti gelombang USBK."
            />
        {:else}
            <Table responsive hover class="mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sesi</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th class="text-center">Kapasitas</th>
                        <th>Ruang</th>
                        {#if ujian.mode_sesi === 'terjadwal'}
                            <th class="text-center">Terjadwal</th>
                        {/if}
                        <th class="text-center">Mengerjakan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each sesis as s (s.id)}
                        {@const st = statusBadge(s)}
                        <tr>
                            <td><strong>{s.nama}</strong></td>
                            <td class="small">
                                {formatTanggal(s.waktu_mulai)}<br />
                                <span class="text-muted">s.d. {formatTanggal(s.waktu_selesai)}</span>
                            </td>
                            <td><Badge color={st.color} pill>{st.label}</Badge></td>
                            <td class="text-center">{s.kapasitas ?? '∞'}</td>
                            <td class="small">{s.ruang?.nama ?? '–'}</td>
                            {#if ujian.mode_sesi === 'terjadwal'}
                                <td class="text-center">{s.jumlah_peserta}</td>
                            {/if}
                            <td class="text-center">{s.jumlah_terisi}</td>
                            <td class="text-end text-nowrap">
                                {#if ujian.mode_sesi === 'terjadwal'}
                                    <Button
                                        color={pilihSesiId === s.id ? 'primary' : 'outline-primary'}
                                        size="sm"
                                        class="me-1"
                                        onclick={() => (pilihSesiId = s.id)}
                                    >
                                        Kelola siswa
                                    </Button>
                                {/if}
                                <Dropdown size="sm" class="d-inline-block">
                                    <DropdownToggle color="outline-secondary" caret>
                                        <i class="bi bi-three-dots"></i>
                                    </DropdownToggle>
                                    <DropdownMenu end>
                                        <DropdownItem>
                                            <a href={AkademikController.kartuPeserta({ ujian: ujian.id, sesi: s.id }).url} target="_blank" rel="noopener" class="text-decoration-none text-dark d-block">
                                                <i class="bi bi-person-badge me-2"></i>Kartu Peserta
                                            </a>
                                        </DropdownItem>
                                        <DropdownItem>
                                            <a href={AkademikController.daftarHadir({ ujian: ujian.id, sesi: s.id }).url} target="_blank" rel="noopener" class="text-decoration-none text-dark d-block">
                                                <i class="bi bi-clipboard-check me-2"></i>Daftar Hadir
                                            </a>
                                        </DropdownItem>
                                        <DropdownItem divider />
                                        <DropdownItem onclick={() => bukaEdit(s)}>
                                            <i class="bi bi-pencil me-2"></i>Edit Sesi
                                        </DropdownItem>
                                        {#if s.bisa_hapus}
                                            <DropdownItem onclick={() => hapusSesi(s)} class="text-danger">
                                                <i class="bi bi-trash me-2"></i>Hapus Sesi
                                            </DropdownItem>
                                        {/if}
                                    </DropdownMenu>
                                </Dropdown>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        {/if}
    </CardBody>
</Card>

{#if sesiAktif}
    <Card class="mb-3">
        <CardHeader>Ruang — {sesiAktif.nama}</CardHeader>
        <CardBody>
            <Label>Ruang ujian</Label>
            <Input
                type="select"
                value={sesiAktif.ruang?.id ?? ''}
                onchange={(e) => aturRuang(sesiAktif, (e.target as HTMLSelectElement).value)}
            >
                <option value="">— Tanpa ruang —</option>
                {#each ruangs as r}<option value={r.id}>{r.kode} — {r.nama}</option>{/each}
            </Input>
            {#if ruangs.length === 0}
                <div class="form-text text-warning">Belum ada ruang. Minta admin menambahkan ruang di menu Kelola Ruang.</div>
            {/if}
        </CardBody>
    </Card>
{/if}

{#if ujian.mode_sesi === 'terjadwal' && sesiAktif}    <div class="row g-3">
        <div class="col-md-6">
            <Card>
                <CardHeader>
                    Jadwalkan siswa ke {sesiAktif.nama}
                    <span class="text-muted small">({siswaBelumTerjadwal.length} belum terjadwal)</span>
                </CardHeader>
                <CardBody>
                    {#if siswaBelumTerjadwal.length === 0}
                        <p class="text-muted small mb-0">Semua siswa sudah terjadwal.</p>
                    {:else}
                        <div class="list-group list-group-flush mb-2" style="max-height:320px;overflow:auto">
                            {#each siswaBelumTerjadwal as sw (sw.nisn)}
                                <label class="list-group-item d-flex align-items-center gap-2 py-2">
                                    <input
                                        type="checkbox"
                                        class="form-check-input mt-0"
                                        checked={nisnDipilih.includes(sw.nisn)}
                                        onchange={() => toggleNisn(sw.nisn)}
                                    />
                                    <span class="small">
                                        <strong>{sw.nama}</strong>
                                        <span class="text-muted"> · {sw.nisn}</span>
                                    </span>
                                </label>
                            {/each}
                        </div>
                        <Button
                            color="primary"
                            size="sm"
                            onclick={jadwalkan}
                            title={nisnDipilih.length === 0 ? 'Pilih siswa terlebih dahulu' : `Jadwalkan ${nisnDipilih.length} siswa`}
                        >
                            Jadwalkan {nisnDipilih.length > 0 ? `(${nisnDipilih.length})` : ''} ke {sesiAktif.nama}
                        </Button>
                        {#if nisnDipilih.length === 0}
                            <div class="form-text">Pilih siswa dari daftar di atas untuk mengaktifkan tombol.</div>
                        {/if}
                    {/if}
                </CardBody>
            </Card>
        </div>
        <div class="col-md-6">
            <Card>
                <CardHeader>
                    Peserta {sesiAktif.nama}
                    <span class="text-muted small">({pesertaSesiAktif.length} siswa)</span>
                </CardHeader>
                <CardBody class="p-0">
                    {#if pesertaSesiAktif.length === 0}
                        <p class="text-muted small p-3 mb-0">Belum ada siswa dijadwalkan ke sesi ini.</p>
                    {:else}
                        <Table responsive hover class="mb-0 align-middle">
                            <tbody>
                                {#each pesertaSesiAktif as p (p.nisn)}
                                    <tr>
                                        <td class="small">
                                            <strong>{p.nama}</strong>
                                            <span class="text-muted"> · {p.nisn}</span>
                                        </td>
                                        <td class="text-end">
                                            <Button
                                                color="outline-danger"
                                                size="sm"
                                                onclick={() => batalkanJadwal(p.nisn)}
                                            >
                                                <i class="bi bi-x-lg"></i>
                                            </Button>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </Table>
                    {/if}
                </CardBody>
            </Card>
        </div>
    </div>
{/if}

<Modal isOpen={modalSesiOpen} toggle={() => (modalSesiOpen = false)}>
    <ModalHeader toggle={() => (modalSesiOpen = false)}>
        {editSesi ? `Ubah ${editSesi.nama}` : 'Tambah Sesi'}
    </ModalHeader>
    <ModalBody>
        <div class="mb-3">
            <Label for="sesiNama">Nama sesi</Label>
            <Input id="sesiNama" bind:value={sesiForm.nama} invalid={!!sesiForm.errors.nama} />
            {#if sesiForm.errors.nama}<div class="invalid-feedback d-block">{sesiForm.errors.nama}</div>{/if}
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <Label for="sesiMulai">Waktu mulai</Label>
                <Input
                    id="sesiMulai"
                    type="datetime-local"
                    bind:value={sesiForm.waktu_mulai}
                    invalid={!!sesiForm.errors.waktu_mulai}
                />
                {#if sesiForm.errors.waktu_mulai}<div class="invalid-feedback d-block">{sesiForm.errors.waktu_mulai}</div>{/if}
            </div>
            <div class="col-6">
                <Label for="sesiSelesai">Waktu selesai</Label>
                <Input
                    id="sesiSelesai"
                    type="datetime-local"
                    bind:value={sesiForm.waktu_selesai}
                    invalid={!!sesiForm.errors.waktu_selesai}
                />
                {#if sesiForm.errors.waktu_selesai}<div class="invalid-feedback d-block">{sesiForm.errors.waktu_selesai}</div>{/if}
            </div>
        </div>
        <div class="mb-1">
            <Label for="sesiKapasitas">Kapasitas (opsional)</Label>
            <Input
                id="sesiKapasitas"
                type="number"
                min="1"
                placeholder="Tanpa batas"
                bind:value={sesiForm.kapasitas}
                invalid={!!sesiForm.errors.kapasitas}
            />
            {#if sesiForm.errors.kapasitas}<div class="invalid-feedback d-block">{sesiForm.errors.kapasitas}</div>{/if}
        </div>
        <p class="text-muted small mb-0">
            Sesi tidak boleh tumpang tindih dan harus di dalam jadwal ujian
            {#if ujian.tanggal_mulai}({formatTanggal(ujian.tanggal_mulai)} s.d. {ujian.tanggal_selesai ? formatTanggal(ujian.tanggal_selesai) : '…'}){/if}.
        </p>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => (modalSesiOpen = false)}>Batal</Button>
        <Button color="primary" onclick={simpanSesi} disabled={sesiForm.processing}>
            {editSesi ? 'Simpan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>
