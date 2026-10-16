<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    type KelompokItem = { nama: string; items: string[] };
    let { kelompokItems = $bindable() }: { kelompokItems: KelompokItem[] } = $props();

    function tambahKelompok() { kelompokItems = [...kelompokItems, { nama: '', items: [''] }]; }
    function tambahItem(gi: number) { kelompokItems[gi].items = [...kelompokItems[gi].items, '']; }
    function hapusItem(gi: number, ii: number) {
        kelompokItems[gi].items = kelompokItems[gi].items.filter((_, x) => x !== ii);
    }
</script>

<div class="mb-2"><span class="fw-semibold">Kelompok</span></div>
{#each kelompokItems as _, gi}
    <Card class="mb-2"><CardBody>
        <input class="form-control mb-2" bind:value={kelompokItems[gi].nama} placeholder="Nama kelompok {gi + 1}" />
        {#each kelompokItems[gi].items as _, ii}
            <div class="d-flex gap-2 mb-1">
                <input class="form-control" bind:value={kelompokItems[gi].items[ii]} placeholder="Item" />
                <Button color="link" class="text-danger p-0" onclick={() => hapusItem(gi, ii)}><i class="bi bi-trash"></i></Button>
            </div>
        {/each}
        <Button color="link" size="sm" class="p-0" onclick={() => tambahItem(gi)}>+ Item</Button>
    </CardBody></Card>
{/each}
<Button color="link" size="sm" onclick={tambahKelompok} class="mb-3 p-0"><i class="bi bi-plus-circle me-1"></i>Tambah kelompok</Button>
