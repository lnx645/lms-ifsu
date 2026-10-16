<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    let { urutanList = $bindable() }: { urutanList: string[] } = $props();

    function tambah() { urutanList = [...urutanList, '']; }
    function hapus(i: number) { urutanList = urutanList.filter((_, x) => x !== i); }
    function pindah(i: number, dir: number) {
        const j = i + dir;
        if (j < 0 || j >= urutanList.length) return;
        const arr = [...urutanList];
        [arr[i], arr[j]] = [arr[j], arr[i]];
        urutanList = arr;
    }
</script>

<div class="mb-2"><span class="fw-semibold">Item <span class="text-muted fw-normal">(urutkan dari atas = jawaban benar)</span></span></div>
{#each urutanList as _, i}
    <Card class="mb-2"><CardBody class="py-2">
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-secondary">{i + 1}</span>
            <input class="form-control flex-grow-1" bind:value={urutanList[i]} placeholder="Item {i + 1}" />
            <Button color="link" class="p-0" onclick={() => pindah(i, -1)} disabled={i === 0} title="Naik"><i class="bi bi-arrow-up"></i></Button>
            <Button color="link" class="p-0" onclick={() => pindah(i, 1)} disabled={i === urutanList.length - 1} title="Turun"><i class="bi bi-arrow-down"></i></Button>
            <Button color="link" class="text-danger p-0" onclick={() => hapus(i)} title="Hapus"><i class="bi bi-trash"></i></Button>
        </div>
    </CardBody></Card>
{/each}
<Button color="link" size="sm" onclick={tambah} class="mb-3 p-0"><i class="bi bi-plus-circle me-1"></i>Tambah item</Button>
