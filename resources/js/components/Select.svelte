<script lang="ts">
    import SvelteSelect from 'svelte-select';
    import type { Snippet } from 'svelte';

    type SelectItem = Record<string, unknown>;

    let {
        items = [],
        value = undefined,
        placeholder = 'Pilih…',
        multiple = false,
        searchable = true,
        clearable = true,
        disabled = false,
        hasError = false,
        invalid = false,
        size = undefined,
        class: className = '',
        onchange,
        oninput,
        onclear,
        ...rest
    }: {
        items?: SelectItem[];
        value?: unknown;
        placeholder?: string;
        multiple?: boolean;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        hasError?: boolean;
        invalid?: boolean;
        size?: 'xs' | 'sm' | 'lg';
        class?: string;
        onchange?: (value: unknown) => void;
        oninput?: (value: unknown) => void;
        onclear?: (value: unknown) => void;
        [key: string]: unknown;
    } = $props();

    const error = $derived(Boolean(hasError || invalid));
    const sizeClass = $derived(size ? `app-select--${size}` : '');
</script>

<div class={`app-select ${sizeClass} ${className}`}>
    <SvelteSelect
        {items}
        {value}
        {placeholder}
        {multiple}
        {searchable}
        {clearable}
        {disabled}
        hasError={error}
        {onchange}
        {oninput}
        {onclear}
        {...rest}
    >
        {#snippet chevronIcon({ listOpen }: { listOpen: boolean })}
            <i
                class={`bi bi-chevron-down app-select__chevron ${listOpen ? 'is-open' : ''}`}
            ></i>
        {/snippet}

        {#snippet clearIcon()}
            <i class="bi bi-x-lg app-select__clear"></i>
        {/snippet}
    </SvelteSelect>
</div>

<style>
    :global(.svelte-select) {
        padding: 0px 8px!important;
    }
    :global(.multi-item-text) {
        display: flex;
        align-items: center;
    }
    :global(.multi-item-clear) > svg {
        width: 10px !important;
        height: 10px !important;
    }
    :global(.selected-item){
        font-size: 13px!important;
    }
</style>
