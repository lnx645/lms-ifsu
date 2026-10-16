<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    type Pasangan = { kiri: string; kanan: string };
    let {
        pasanganList = $bindable(),
        pengecohList = $bindable([]),
        allowPengecoh = false,
    }: {
        pasanganList: Pasangan[];
        pengecohList?: string[];
        allowPengecoh?: boolean;
    } = $props();

    function tambah() { pasanganList = [...pasanganList, { kiri: '', kanan: '' }]; }
    function hapus(i: number) { pasanganList = pasanganList.filter((_, x) => x !== i); }
    function tambahPengecoh() { pengecohList = [...(pengecohList ?? []), '']; }
    function hapusPengecoh(i: number) { pengecohList = (pengecohList ?? []).filter((_, x) => x !== i); }
</script>

<div class="mb-2"><span class="fw-semibold">Pasangan (1 lawan 1)</span></div>
{#each pasanganList as _, i}
    <Card class="mb-2"><CardBody class="py-2">
        <div class="row g-2 align-items-center">
            <div class="col-5"><input class="form-control" bind:value={pasanganList[i].kiri} placeholder="Kiri {i + 1}" /></div>
            <div class="col-auto"><i class="bi bi-arrow-left-right text-muted"></i></div>
            <div class="col-5"><input class="form-control" bind:value={pasanganList[i].kanan} placeholder="Kanan {i + 1}" /></div>
            <div class="col-auto"><Button color="link" class="text-danger p-0" onclick={() => hapus(i)}><i class="bi bi-trash"></i></Button></div>
        </div>
    </CardBody></Card>
{/each}
<Button color="link" size="sm" onclick={tambah} class="mb-3 p-0"><i class="bi bi-plus-circle me-1"></i>Tambah pasangan</Button>

{#if allowPengecoh}
    <div class="mb-2 mt-3">
        <span class="fw-semibold">Pengecoh</span>
        <span class="text-muted small ms-2">— opsi kanan tambahan yang tidak punya pasangan (siswa harus jeli)</span>
    </div>
    {#each (pengecohList ?? []) as _, i}
        <Card class="mb-2 border-warning"><CardBody class="py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto"><span class="badge bg-warning text-dark">P{i + 1}</span></div>
                <div class="col"><input class="form-control" bind:value={pengecohList[i]} placeholder="Teks pengecoh {i + 1}" /></div>
                <div class="col-auto"><Button color="link" class="text-danger p-0" onclick={() => hapusPengecoh(i)}><i class="bi bi-trash"></i></Button></div>
            </div>
        </CardBody></Card>
    {/each}
    <Button color="link" size="sm" onclick={tambahPengecoh} class="p-0"><i class="bi bi-plus-circle me-1"></i>Tambah pengecoh</Button>
{/if}
