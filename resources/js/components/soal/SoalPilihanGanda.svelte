<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import RichTextEditor from '@/components/RichTextEditor.svelte';

    type Opsi = { id: string; teks: string };

    let {
        opsiList = $bindable(),
        kunci = $bindable(''),
        kompleks = false,
        kunciKompleks = $bindable([] as string[]),
    }: {
        opsiList: Opsi[];
        kunci?: string;
        kompleks?: boolean;
        kunciKompleks?: string[];
    } = $props();

    const huruf = (i: number) => String.fromCharCode(97 + i);

    function tambah() {
        opsiList = [...opsiList, { id: huruf(opsiList.length), teks: '' }];
    }
    function hapus(i: number) {
        if (opsiList.length <= 2) return;
        opsiList = opsiList.filter((_, x) => x !== i).map((o, x) => ({ ...o, id: huruf(x) }));
    }
    function toggle(id: string) {
        kunciKompleks = kunciKompleks.includes(id)
            ? kunciKompleks.filter(k => k !== id)
            : [...kunciKompleks, id];
    }
</script>

<div class="mb-2 d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Opsi jawaban</span>
    <span class="text-muted small">{kompleks ? 'Centang untuk kunci (bisa banyak)' : 'Pilih radio untuk kunci'}</span>
</div>

{#each opsiList as opsi, i}
    <Card class="mb-2"><CardBody class="py-2">
        <div class="d-flex gap-2 align-items-start">
            <div class="d-flex flex-column align-items-center pt-1" style="min-width:36px">
                <span class="badge bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px">{opsi.id}</span>
                {#if kompleks}
                    <input type="checkbox" class="form-check-input mt-1" checked={kunciKompleks.includes(opsi.id)} onchange={() => toggle(opsi.id)} title="Kunci" />
                {:else}
                    <input type="radio" name="kunci-pg" class="form-check-input mt-1" checked={kunci === opsi.id} onchange={() => (kunci = opsi.id)} title="Kunci" />
                {/if}
            </div>
            <div class="flex-grow-1">
                <RichTextEditor id="opsi-{opsi.id}" value={opsi.teks} placeholder="Opsi {opsi.id}..." onchange={(h) => { opsiList[i].teks = h; }} />
            </div>
            <Button color="link" class="text-danger p-1" onclick={() => hapus(i)} disabled={opsiList.length <= 2} title="Hapus">
                <i class="bi bi-trash"></i>
            </Button>
        </div>
    </CardBody></Card>
{/each}

<Button color="link" size="sm" onclick={tambah} class="mb-3 p-0">
    <i class="bi bi-plus-circle me-1"></i>Tambah opsi
</Button>
