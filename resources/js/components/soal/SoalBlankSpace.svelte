<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    let {
        teks = $bindable(''),
        kunci = $bindable([] as string[]),
    }: { teks: string; kunci: string[] } = $props();
</script>

<Card class="mb-3"><CardBody>
    <div class="fw-semibold mb-2">Teks dengan titik-titik</div>
    <div class="alert alert-light border small mb-2">
        Tulis teks, gunakan <code>[___]</code> untuk bagian yang dikosongkan.<br />
        Contoh: "Ibukota Indonesia adalah [___]."
    </div>
    <textarea class="form-control mb-3" rows="4" bind:value={teks} placeholder="Tulis teks dengan [___]..."></textarea>
    <div class="fw-semibold mb-2">Kunci jawaban <span class="text-muted fw-normal">(urut sesuai [___])</span></div>
    {#each kunci as _, i}
        <div class="d-flex gap-2 mb-1 align-items-center">
            <span class="badge bg-secondary">{i + 1}</span>
            <input class="form-control" bind:value={kunci[i]} placeholder="Jawaban {i + 1}" />
            <Button color="link" class="text-danger p-0" onclick={() => { kunci = kunci.filter((_, x) => x !== i); }}>
                <i class="bi bi-trash"></i>
            </Button>
        </div>
    {/each}
    <Button color="link" size="sm" class="p-0" onclick={() => { kunci = [...kunci, '']; }}>
        <i class="bi bi-plus-circle me-1"></i>Tambah kunci
    </Button>
</CardBody></Card>
