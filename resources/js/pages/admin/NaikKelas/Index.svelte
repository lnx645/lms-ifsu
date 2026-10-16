<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import Select from '@/components/Select.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import NaikKelasController from '@/actions/App/Http/Controllers/Admin/NaikKelasController';
    import { confirm } from '@/lib/confirm.svelte';
    import { extractId } from '@/lib/utils';
    import type { SelectOption } from '@/types/models';

    type Status = 'naik' | 'tinggal' | 'lulus';
    type SiswaItem = { nisn: string; nama: string; status: Status };
    type KelasPreview = {
        kelas_asal: string;
        kelas_target: string | null;
        kelas_target_id: number | null;
        tingkat: string | null;
        siswa: SiswaItem[];
    };
    type KelasTujuanOption = { value: number; label: string; jurusan: string };
    type Ringkasan = { naik: number; tinggal: number; lulus: number };
    type Preview = {
        sumber: { id: number; name: string };
        target: { id: number; name: string };
        kelas: KelasPreview[];
        ringkasan: Ringkasan;
        kelas_tujuan: Record<string, KelasTujuanOption[]>;
    };
    type PilihanSiswa = { status: Status; kelas_target: number | null };

    let {
        tahun_ajaran = [],
        preview = null,
    }: {
        tahun_ajaran: { id: number; name: string; active: boolean }[];
        preview: Preview | null;
    } = $props();

    const tahunAjaranOptions = $derived<SelectOption[]>(
        tahun_ajaran.map((t) => ({ value: t.id, label: t.name })),
    );

    const statusOptions: SelectOption[] = [
        { value: 'naik', label: 'Naik' },
        { value: 'tinggal', label: 'Tinggal' },
        { value: 'lulus', label: 'Lulus' },
    ];

    const tujuanOptions = $derived<Record<string, KelasTujuanOption[]>>(
        preview?.kelas_tujuan ?? {},
    );

    function kelompokTujuan(
        tingkat: string,
    ): Record<string, KelasTujuanOption[]> {
        const grouped: Record<string, KelasTujuanOption[]> = {};
        for (const opt of tujuanOptions[tingkat] ?? []) {
            const key = opt.jurusan || 'Tanpa jurusan';
            (grouped[key] ??= []).push(opt);
        }
        return grouped;
    }

    let pilihan = $state<Record<string, PilihanSiswa>>({});
    let processing = $state(false);
    let lastPreviewKey = $state<string | null>(null);

    const form = useForm({
        tahun_ajaran_sumber: null as number | null,
        tahun_ajaran_target: null as number | null,
    });

    function initPilihan() {
        const next: Record<string, PilihanSiswa> = {};
        for (const k of preview?.kelas ?? []) {
            for (const s of k.siswa) {
                next[s.nisn] = { status: s.status, kelas_target: k.kelas_target_id };
            }
        }
        pilihan = next;
    }

    $effect(() => {
        if (!preview) {
            return;
        }

        const key = `${preview.sumber.id}:${preview.target.id}`;
        if (lastPreviewKey === key) {
            return;
        }
        lastPreviewKey = key;
        form.tahun_ajaran_sumber = preview.sumber.id;
        form.tahun_ajaran_target = preview.target.id;
        initPilihan();
    });

    function statusOf(siswa: SiswaItem): Status {
        return pilihan[siswa.nisn]?.status ?? siswa.status;
    }

    function setStatus(siswa: SiswaItem, status: Status) {
        pilihan[siswa.nisn] = { status, kelas_target: pilihan[siswa.nisn]?.kelas_target ?? null };
    }

    function setKelasTujuan(siswa: SiswaItem, kelasTujuanId: number | null) {
        pilihan[siswa.nisn] = {
            status: statusOf(siswa),
            kelas_target: kelasTujuanId,
        };
    }

    function kelasTujuanLabel(nisn: string): string {
        const id = pilihan[nisn]?.kelas_target;
        if (!id) {
            return '';
        }
        for (const list of Object.values(tujuanOptions)) {
            const opt = list.find((o) => o.value === id);
            if (opt) {
                return opt.label;
            }
        }
        return '';
    }

    const ringkasan = $derived.by<Ringkasan>(() => {
        const r: Ringkasan = { naik: 0, tinggal: 0, lulus: 0 };
        for (const p of Object.values(pilihan)) {
            r[p.status]++;
        }
        return r;
    });

    function lihatPreview() {
        form.post(NaikKelasController.preview().url, {
            preserveScroll: true,
            preserveUrl: true,
        });
    }

    async function prosesNaikKelas() {
        const pilihanList = Object.entries(pilihan).map(([nisn, p]) => ({
            nisn,
            status: p.status,
            kelas_target: p.status === 'naik' ? p.kelas_target : null,
        }));

        const ok = await confirm.show({
            title: 'Proses Naik Kelas?',
            message: `${ringkasan.naik} naik, ${ringkasan.tinggal} tinggal, dan ${ringkasan.lulus} lulus akan diproses dari ${preview?.sumber.name ?? ''} ke ${preview?.target.name ?? ''}. Lanjutkan?`,
            confirmText: 'Ya, Proses',
            color: 'primary',
        });

        if (!ok) {
            return;
        }

        processing = true;
        router.post(NaikKelasController.execute().url, {
            tahun_ajaran_sumber: form.tahun_ajaran_sumber,
            tahun_ajaran_target: form.tahun_ajaran_target,
            pilihan: pilihanList,
        }, {
            onFinish: () => (processing = false),
        });
    }

    function badgeColor(status: Status): 'success' | 'warning' | 'danger' {
        if (status === 'lulus') {
            return 'danger';
        }
        return status === 'naik' ? 'success' : 'warning';
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Naik Kelas"
        subtitle="Pindahkan peserta didik ke kelas tingkat berikutnya per tahun ajaran."
    >
        {#snippet actions()}
            <Button
                color="primary"
                onclick={lihatPreview}
                disabled={form.processing || !form.tahun_ajaran_sumber || !form.tahun_ajaran_target}
            >
                <i class="bi bi-search me-1"></i> Lihat Preview
            </Button>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-sm mb-3">
        <CardBody>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="tahun-ajaran-sumber" class="form-label"
                        >Tahun Ajaran Sumber</label
                    >
                    <Select
                        id="tahun-ajaran-sumber"
                        items={tahunAjaranOptions}
                        value={form.tahun_ajaran_sumber}
                        placeholder="Pilih tahun ajaran…"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => (form.tahun_ajaran_sumber = extractId(v))}
                    />
                </div>
                <div class="col-md-6">
                    <label for="tahun-ajaran-target" class="form-label"
                        >Tahun Ajaran Target</label
                    >
                    <Select
                        id="tahun-ajaran-target"
                        items={tahunAjaranOptions}
                        value={form.tahun_ajaran_target}
                        placeholder="Pilih tahun ajaran…"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => (form.tahun_ajaran_target = extractId(v))}
                    />
                </div>
            </div>
        </CardBody>
    </Card>

    {#if preview}
        <Card class="border rounded-1 shadow-sm mb-3">
            <CardBody>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <Badge color="primary">
                        {preview.sumber.name}
                        <i class="bi bi-arrow-right mx-1"></i>
                        {preview.target.name}
                    </Badge>
                    <Badge color="success">
                        {ringkasan.naik} naik
                    </Badge>
                    <Badge color="warning">
                        {ringkasan.tinggal} tinggal
                    </Badge>
                    <Badge color="danger">
                        {ringkasan.lulus} lulus
                    </Badge>
                </div>
            </CardBody>
        </Card>

        {#each preview.kelas as kelas (kelas.kelas_asal)}
            <Card class="border rounded-1 shadow-sm mb-3">
                <CardBody>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <i class="bi bi-collection text-primary"></i>
                        <span class="fw-semibold">{kelas.kelas_asal}</span>
                        <i class="bi bi-arrow-right text-secondary"></i>
                        <span class="fw-semibold text-primary">
                            {kelas.kelas_target ?? 'Tidak ada kelas tujuan'}
                        </span>
                        {#if kelas.tingkat}
                            <Badge color="secondary">{kelas.tingkat}</Badge>
                        {/if}
                    </div>

                    {#if kelas.siswa.length}
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">NISN</th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">Status</th>
                                        {#if tujuanOptions[kelas.tingkat ?? '']?.length}
                                            <th scope="col">Pindah ke Kelas</th>
                                        {/if}
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each kelas.siswa as siswa (siswa.nisn)}
                                        <tr>
                                            <td class="text-muted">
                                                {siswa.nisn}
                                            </td>
                                            <td>
                                                {siswa.nama}
                                                {#if kelasTujuanLabel(siswa.nisn)}
                                                    <div class="small text-muted">
                                                        <i class="bi bi-arrow-right-circle me-1"></i>
                                                        {kelasTujuanLabel(siswa.nisn)}
                                                    </div>
                                                {/if}
                                            </td>
                                            <td>
                                                <div
                                                    class="d-inline-flex align-items-center gap-2"
                                                >
                                                    <Badge color={badgeColor(statusOf(siswa))}>
                                                        {statusOf(siswa)}
                                                    </Badge>
                                                    <select
                                                        class="form-select form-select-sm w-auto"
                                                        value={statusOf(siswa)}
                                                        onchange={(e) =>
                                                            setStatus(
                                                                siswa,
                                                                (e.currentTarget as HTMLSelectElement)
                                                                    .value as Status,
                                                            )}
                                                    >
                                                        {#each statusOptions as opt (opt.value)}
                                                            <option
                                                                value={opt.value}
                                                            >
                                                                {opt.label}
                                                            </option>
                                                        {/each}
                                                    </select>
                                                </div>
                                            </td>
                                            {#if tujuanOptions[kelas.tingkat ?? '']?.length}
                                                <td>
                                                    {#if statusOf(siswa) === 'naik'}
                                                        <select
                                                            class="form-select form-select-sm w-auto"
                                                            value={pilihan[siswa.nisn]
                                                                ?.kelas_target ?? ''}
                                                            onchange={(e) =>
                                                                setKelasTujuan(
                                                                    siswa,
                                                                    Number(
                                                                        (e.currentTarget as HTMLSelectElement)
                                                                            .value,
                                                                    ) || null,
                                                                )}
                                                        >
                                                            <option value="">
                                                                Kelas tujuan (otomatis)
                                                            </option>
                                                            {#each Object.entries(
                                                                kelompokTujuan(kelas.tingkat ?? ''),
                                                            ) as [jurusan, options] (jurusan)}
                                                                <optgroup label={jurusan}>
                                                                    {#each options as opt (opt.value)}
                                                                        <option value={opt.value}>
                                                                            {opt.label}
                                                                        </option>
                                                                    {/each}
                                                                </optgroup>
                                                            {/each}
                                                        </select>
                                                    {:else}
                                                        <span class="text-muted">—</span>
                                                    {/if}
                                                </td>
                                            {/if}
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <div class="text-center text-secondary py-3">
                            Tidak ada peserta didik aktif di kelas ini.
                        </div>
                    {/if}
                </CardBody>
            </Card>
        {/each}

        {#if Object.keys(pilihan).length}
            <div class="d-flex justify-content-end mb-4">
                <Button
                    color="primary"
                    onclick={prosesNaikKelas}
                    disabled={form.processing || processing}
                >
                    <i class="bi bi-arrow-up-circle me-1"></i> Proses Naik Kelas
                </Button>
            </div>
        {/if}
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-arrow-up-circle" style="font-size: 3rem"></i>
                    <p class="mt-3 mb-0">
                        Pilih tahun ajaran sumber &amp; target, lalu klik
                        <strong>Lihat Preview</strong> untuk melihat daftar peserta
                        didik yang akan dinaikkan.
                    </p>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>