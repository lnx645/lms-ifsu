<script lang="ts">
    // Renderer: Listening (audio + jawaban text)
    let {
        detail,
        jawaban = $bindable(''),
        disabled = false,
    }: {
        detail: { audio: string };
        jawaban?: string;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};
</script>

{#if (safe.audio ?? "")}
    <audio controls src={(safe.audio ?? "")} class="w-100 mb-3"></audio>
{/if}
<textarea class="form-control" rows="3" bind:value={jawaban} disabled={disabled} placeholder="Tulis jawaban berdasarkan audio..."></textarea>
