<script lang="ts">
    import Latex from '@/components/Latex.svelte';

    let {
        detail,
        jawaban = $bindable({} as Record<string, string>),
        disabled = false,
    }: {
        detail: { kelompok: { nama: string; items: string[] }[] };
        jawaban?: Record<string, string>;
        disabled?: boolean;
    } = $props();

    const safe = detail ?? {};
    // Format siswa (strip): nama_kelompok + pool. Format guru: kelompok.
    const kelompoks = $derived(
        safe.kelompok ?? (safe.nama_kelompok?.map((nama: string) => ({ nama, items: [] })) ?? [])
    );
    // Semua item dari semua kelompok (diacak)
    const semuaItems = $derived.by(() => {
        if (safe.pool) return [...safe.pool];
        const items: string[] = [];
        for (const k of kelompoks) {
            for (const it of (k.items ?? [])) items.push(it);
        }
        // acak stabil sekali
        return items;
    });

    let selectedItem = $state<string | null>(null);
    // warna per kelompok
    const warna = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];

    const jbn = () => jawaban ?? {};

    function klikItem(item: string) {
        if (disabled) return;
        // kalau sudah dikelompokkan, kembalikan ke pool (unassign)
        if (jbn()[item]) {
            const nj = { ...jbn() };
            delete nj[item];
            jawaban = nj;
            selectedItem = null;
            return;
        }
        selectedItem = selectedItem === item ? null : item;
    }

    function klikKelompok(namaKelompok: string) {
        if (disabled || !selectedItem) return;
        jawaban = { ...jbn(), [selectedItem]: namaKelompok };
        selectedItem = null;
    }

    function itemsDiKelompok(nama: string): string[] {
        return semuaItems.filter(it => jbn()[it] === nama);
    }

    function belumDikelompokkan(): string[] {
        return semuaItems.filter(it => !jbn()[it]);
    }
</script>

<div class="alert alert-light border small mb-2">
    <i class="bi bi-info-circle me-1"></i>
    Ketuk <strong>item</strong>, lalu ketuk <strong>kotak kelompok</strong> tujuannya. Ketuk item yang sudah dikelompokkan untuk mengembalikannya.
</div>

<!-- Pool item yang belum dikelompokkan -->
{#if belumDikelompokkan().length > 0}
    <div class="mb-3">
        <div class="small text-muted mb-2"><i class="bi bi-box-seam me-1"></i>Belum dikelompokkan ({belumDikelompokkan().length})</div>
        <div class="d-flex flex-wrap gap-2">
            {#each belumDikelompokkan() as item}
                <button type="button"
                    class="btn btn-sm {selectedItem === item ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill px-3"
                    onclick={() => klikItem(item)} disabled={disabled}>
                    <Latex teks={item} />
                </button>
            {/each}
        </div>
    </div>
{/if}

<!-- Kotak-kotak kelompok -->
<div class="row g-2">
    {#each kelompoks as k, i}
        <div class="col-12 col-md-6">
            <div class="kelompok-box border rounded p-2 {selectedItem ? 'kelompok-aktif' : ''}"
                style="cursor:{disabled || !selectedItem ? 'default' : 'pointer'}; min-height: 90px;"
                onclick={() => klikKelompok(k.nama)} role="button" tabindex="0"
                onkeydown={(e) => { if (e.key === 'Enter') klikKelompok(k.nama); }}>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-{warna[i % warna.length]}">{k.nama}</span>
                    <span class="small text-muted">{itemsDiKelompok(k.nama).length} item</span>
                </div>
                <div class="d-flex flex-wrap gap-1">
                    {#each itemsDiKelompok(k.nama) as item}
                        <span class="badge bg-{warna[i % warna.length]} bg-opacity-25 text-dark border rounded-pill px-2 py-1"
                            style="cursor:{disabled ? 'default' : 'pointer'}"
                            onclick={(e) => { e.stopPropagation(); klikItem(item); }} role="button" tabindex="0"
                            onkeydown={(e) => { if (e.key === 'Enter') { e.stopPropagation(); klikItem(item); } }}>
                            <Latex teks={item} />
                            <i class="bi bi-x ms-1"></i>
                        </span>
                    {:else}
                        <span class="text-muted small fst-italic px-1">
                            {selectedItem ? 'Ketuk untuk taruh di sini' : 'Kosong'}
                        </span>
                    {/each}
                </div>
            </div>
        </div>
    {/each}
</div>

<style>
    .kelompok-box {
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        background: #fff;
    }
    .kelompok-aktif {
        border-color: #0d6efd !important;
        border-style: dashed !important;
        background: rgba(13, 110, 253, 0.04);
    }
    .kelompok-aktif:hover {
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    }
</style>
