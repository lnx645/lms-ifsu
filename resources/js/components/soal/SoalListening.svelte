<script lang="ts">
    import { Card, CardBody } from '@sveltestrap/sveltestrap';

    let {
        audioUrl = $bindable(''),
    }: { audioUrl: string } = $props();

    let file = $state<File | null>(null);

    function onFile(e: Event) {
        const f = (e.target as HTMLInputElement).files?.[0];
        if (f) { file = f; audioUrl = URL.createObjectURL(f); }
    }
</script>

<Card class="mb-3"><CardBody>
    <div class="fw-semibold mb-2">File Audio</div>
    <input type="file" accept="audio/*" class="form-control mb-2" onchange={onFile} />
    <div class="form-text mb-2">Upload MP3/WAV/OGG, atau tempel URL di bawah.</div>
    <label class="form-label small">URL Audio (opsional)</label>
    <input class="form-control mb-2" bind:value={audioUrl} placeholder="https://..." />
    {#if audioUrl}<audio controls src={audioUrl} class="w-100"></audio>{/if}
    <div class="alert alert-light border small mt-2 mb-0">
        <i class="bi bi-info-circle me-1"></i>Tulis pertanyaan di editor atas berdasarkan audio ini.
    </div>
</CardBody></Card>
