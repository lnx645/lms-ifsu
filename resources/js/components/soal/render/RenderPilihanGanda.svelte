<script lang="ts">
    import Latex from '@/components/Latex.svelte';
    // Renderer: Pilihan Ganda (single & kompleks)
    let {
        detail,
        kompleks = false,
        jawaban = $bindable(null),
        disabled = false,
    }: {
        detail: { opsi: { teks: string }[]; kunci?: string | string[] };
        kompleks?: boolean;
        jawaban?: string | string[] | null;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    const huruf = (i: number) => String.fromCharCode(97 + i);
    const groupName = `pg-${Math.random().toString(36).slice(2)}`;

    function pilih(id: string) {
        if (disabled) return;
        if (kompleks) {
            const arr = Array.isArray(jawaban) ? [...jawaban] : [];
            jawaban = arr.includes(id) ? arr.filter(x => x !== id) : [...arr, id];
        } else {
            jawaban = id;
        }
    }

    function isChecked(id: string): boolean {
        return kompleks
            ? Array.isArray(jawaban) && jawaban.includes(id)
            : jawaban === id;
    }
</script>

<div class="d-flex flex-column gap-2">
    {#each (safe.opsi ?? []) as opsi, i}
        {@const id = huruf(i)}
        <label class="border rounded p-2 d-flex gap-2 align-items-start {isChecked(id) ? 'border-primary bg-light' : ''}"
            style="cursor: {disabled ? 'default' : 'pointer'}">
            <input
                type={kompleks ? 'checkbox' : 'radio'}
                name={groupName}
                checked={isChecked(id)}
                onchange={() => pilih(id)}
                disabled={disabled}
                class="form-check-input mt-1"
            />
            <span class="fw-bold text-muted">{id}.</span>
            <span class="flex-grow-1"><Latex teks={opsi.teks ?? ""} /></span>
        </label>
    {/each}
</div>
