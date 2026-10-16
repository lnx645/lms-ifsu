<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    type Pernyataan = { teks: string; jawaban: string };
    let {
        pernyataanList = $bindable(),
        tipe = 'benar_salah',
    }: {
        pernyataanList: Pernyataan[];
        tipe?: 'benar_salah' | 'setuju_tidak';
    } = $props();

    const opsiJawaban = tipe === 'benar_salah'
        ? [{ v: 'benar', l: 'Benar' }, { v: 'salah', l: 'Salah' }]
        : [{ v: 'setuju', l: 'Setuju' }, { v: 'tidak', l: 'Tidak' }];

    function tambah() { pernyataanList = [...pernyataanList, { teks: '', jawaban: '' }]; }
    function hapus(i: number) { pernyataanList = pernyataanList.filter((_, x) => x !== i); }
</script>

<div class="mb-2"><span class="fw-semibold">Pernyataan</span></div>
{#each pernyataanList as _, i}
    <Card class="mb-2"><CardBody class="py-2">
        <div class="row g-2 align-items-center">
            <div class="col-8"><input class="form-control" bind:value={pernyataanList[i].teks} placeholder="Pernyataan {i + 1}" /></div>
            <div class="col-3">
                <select class="form-select" bind:value={pernyataanList[i].jawaban}>
                    <option value="">Pilih</option>
                    {#each opsiJawaban as o}<option value={o.v}>{o.l}</option>{/each}
                </select>
            </div>
            <div class="col-1"><Button color="link" class="text-danger p-0" onclick={() => hapus(i)}><i class="bi bi-trash"></i></Button></div>
        </div>
    </CardBody></Card>
{/each}
<Button color="link" size="sm" onclick={tambah} class="mb-3 p-0"><i class="bi bi-plus-circle me-1"></i>Tambah pernyataan</Button>
