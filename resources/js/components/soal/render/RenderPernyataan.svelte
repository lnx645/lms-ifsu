<script lang="ts">
    // Renderer: Benar/Salah & Setuju/Tidak
    let {
        detail,
        tipe = 'benar_salah',
        jawaban = $bindable({} as Record<number, string>),
        disabled = false,
    }: {
        detail: { pernyataan: { teks: string; jawaban: string }[] };
        tipe?: string;
        jawaban?: Record<number, string>;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};
    // Helper aman untuk akses jawaban
    const jbn = (k: any) => (jawaban ?? {})[k];

    const uid = Math.random().toString(36).slice(2);
    const opsi = tipe === 'benar_salah'
        ? [{ v: 'benar', l: 'Benar' }, { v: 'salah', l: 'Salah' }]
        : [{ v: 'setuju', l: 'Setuju' }, { v: 'tidak', l: 'Tidak' }];
</script>

{#each (safe.pernyataan ?? []) as p, i}
    <div class="border rounded p-2 mb-2">
        <div class="mb-2">{@html p.teks}</div>
        <div class="d-flex gap-3">
            {#each opsi as o}
                <label class="d-flex gap-1 align-items-center" style="cursor:pointer">
                    <input type="radio" name="p-{uid}-{i}" value={o.v}
                        checked={(jawaban ?? {})[i] === o.v}
                        onchange={() => { if (!disabled) jawaban = { ...(jawaban ?? {}), [i]: o.v }; }}
                        disabled={disabled} class="form-check-input" />
                    <span>{o.l}</span>
                </label>
            {/each}
        </div>
    </div>
{/each}
