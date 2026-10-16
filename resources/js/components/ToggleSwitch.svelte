<script lang="ts">
    let {
        checked,
        label,
        disabled = false,
        onchange,
    }: {
        checked: boolean;
        label?: string;
        disabled?: boolean;
        onchange?: (value: boolean) => void;
    } = $props();
</script>

<label
    class="app-toggle"
    class:app-toggle--disabled={disabled}
>
    <button
        type="button"
        class="app-toggle__track"
        class:is-on={checked}
        role="switch"
        aria-checked={checked ? 'true' : 'false'}
        aria-label={label ?? 'Toggle'}
        disabled={disabled}
        onclick={() => onchange?.(!checked)}
    >
        <span class="app-toggle__knob"></span>
    </button>
    {#if label}<span class="app-toggle__label">{label}</span>{/if}
</label>

<style>
    .app-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
    }

    .app-toggle--disabled {
        opacity: 0.5;
    }

    .app-toggle__label {
        margin: 0;
        cursor: pointer;
    }

    .app-toggle__track {
        position: relative;
        width: 46px;
        height: 26px;
        border-radius: 999px;
        border: none;
        background: var(--bs-secondary);
        cursor: pointer;
        padding: 0;
        transition: background 0.2s ease;
    }

    .app-toggle__track:disabled {
        cursor: not-allowed;
    }

    .app-toggle__track.is-on {
        background: var(--bs-success);
    }

    .app-toggle__knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--inv-white);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s ease;
    }

    .app-toggle__track.is-on .app-toggle__knob {
        transform: translateX(20px);
    }
</style>
