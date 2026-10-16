<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    type Marker = { x: number; y: number; label: string; kunci: string };
    let {
        imageUrl = $bindable(''),
        markers = $bindable([] as Marker[]),
    }: { imageUrl: string; markers: Marker[] } = $props();

    function onImageClick(e: MouseEvent) {
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        markers = [...markers, { x, y, label: `Titik ${markers.length + 1}`, kunci: '' }];
    }
</script>

<Card class="mb-3"><CardBody>
    <div class="fw-semibold mb-2">Gambar</div>
    <input class="form-control mb-2" bind:value={imageUrl} placeholder="URL gambar..." />
    <div class="form-text mb-2">Tempel URL gambar, atau upload via editor pertanyaan di atas.</div>

    {#if imageUrl}
        <div class="fw-semibold mb-2">Tandai bagian gambar <span class="text-muted fw-normal">(klik pada gambar)</span></div>
        <div class="position-relative border rounded mb-3" style="cursor:crosshair;max-width:100%" onclick={onImageClick}>
            <img src={imageUrl} alt="Label" style="max-width:100%;display:block" />
            {#each markers as m, i}
                <div class="position-absolute badge bg-danger rounded-circle d-flex align-items-center justify-content-center"
                    style="left:{m.x}%;top:{m.y}%;width:24px;height:24px;transform:translate(-50%,-50%)">{i + 1}</div>
            {/each}
        </div>
        {#each markers as _, i}
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-auto"><span class="badge bg-danger rounded-circle">{i + 1}</span></div>
                <div class="col-5"><input class="form-control form-control-sm" bind:value={markers[i].label} placeholder="Label" /></div>
                <div class="col-5"><input class="form-control form-control-sm" bind:value={markers[i].kunci} placeholder="Kunci" /></div>
                <div class="col-auto"><Button color="link" class="text-danger p-0" onclick={() => { markers = markers.filter((_, x) => x !== i); }}><i class="bi bi-trash"></i></Button></div>
            </div>
        {/each}
    {/if}
</CardBody></Card>
