<script lang="ts">
    // Renderer: Blank Space (isi titik-titik) - inline natural
    let {
        detail,
        jawaban = $bindable([] as string[]),
        disabled = false,
    }: {
        detail: { teks: string; kunci: string[] };
        jawaban?: string[];
        disabled?: boolean;
    } = $props();

    const safe = detail ?? {};

    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.teks ?? '')) {
            initialized = true;
            const count = ((safe.teks ?? '').match(/\[___\]/g) || []).length;
            if ((jawaban ?? []).length !== count) {
                jawaban = new Array(count).fill('');
            }
        }
    });

    function parts(): ({ type: 'text'; content: string } | { type: 'blank'; index: number })[] {
        const result: ({ type: 'text'; content: string } | { type: 'blank'; index: number })[] = [];
        let idx = 0;
        for (const seg of (safe.teks ?? '').split('[___]')) {
            if (result.length > 0) result.push({ type: 'blank', index: idx++ });
            if (seg) result.push({ type: 'text', content: seg });
        }
        return result;
    }

    function setJawaban(i: number, v: string) {
        const a = [...(jawaban ?? [])];
        a[i] = v;
        jawaban = a;
    }
</script>

<div class="blank-text">
    {#each parts() as p}
        {#if p.type === 'text'}
            <span>{@html p.content}</span>
        {:else}
            <input
                class="blank-input"
                value={(jawaban ?? [])[p.index] ?? ''}
                oninput={(e) => setJawaban(p.index, (e.target as HTMLInputElement).value)}
                disabled={disabled}
                placeholder="........"
                aria-label="Isian {p.index + 1}" />
        {/if}
    {/each}
</div>

<style>
    .blank-text {
        line-height: 2.2;
        font-size: 1rem;
    }
    .blank-input {
        display: inline-block;
        width: 110px;
        max-width: 40vw;
        border: none;
        border-bottom: 2px solid #6c757d;
        background: transparent;
        text-align: center;
        font-size: 0.95rem;
        padding: 0 4px 2px;
        margin: 0 4px;
        vertical-align: baseline;
        outline: none;
        transition: border-color 0.15s;
    }
    .blank-input:focus {
        border-bottom-color: #0d6efd;
        background: rgba(13, 110, 253, 0.05);
    }
    .blank-input:disabled {
        border-bottom-style: dotted;
        color: inherit;
        background: transparent;
    }
    .blank-input::placeholder {
        color: #adb5bd;
        letter-spacing: 2px;
    }
</style>
