<script lang="ts">
    let {
        value,
        max,
        error = null,
        disabled = false,
        onchange,
    }: {
        value: number | '' | null;
        max: number;
        error?: string | null;
        disabled?: boolean;
        onchange: (nilai: number | '') => void;
    } = $props();

    const uid = $state(Math.random().toString(36).slice(2, 8));

    const sliderValue = $derived(value === null || value === '' ? 0 : value);

    function onSliderInput(e: Event) {
        onchange(Number((e.currentTarget as HTMLInputElement).value));
    }

    function onNumberInput(e: Event) {
        const raw = (e.currentTarget as HTMLInputElement).value;
        onchange(raw === '' ? '' : Number(raw));
    }
</script>

<div class="d-flex justify-content-between align-items-center mb-1">
    <label for="nilai-slider-{uid}" class="form-label fw-semibold mb-0">Nilai</label>
    <span class="fw-bold fs-5 {error ? 'text-danger' : 'text-primary'}">
        {value === '' || value === null ? '—' : value}
        <span class="text-muted fs-6 fw-normal"> / {max}</span>
    </span>
</div>
<input
    id="nilai-slider-{uid}"
    type="range"
    class="form-range"
    min="0"
    max={max}
    step="0.1"
    value={sliderValue}
    oninput={onSliderInput}
    disabled={disabled}
/>
<input
    type="number"
    min="0"
    max={max}
    step="0.1"
    class="form-control {error ? 'is-invalid' : ''}"
    value={value ?? ''}
    oninput={onNumberInput}
    disabled={disabled}
    placeholder="Isi nilai…"
/>
{#if error}
    <small class="text-danger d-block mt-1">{error}</small>
{/if}
