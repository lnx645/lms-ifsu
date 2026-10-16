<script lang="ts">
    import { onMount } from 'svelte';
    import { Calendar } from 'vanilla-calendar-pro';
    import type { Calendar as CalendarInstance } from 'vanilla-calendar-pro';
    import 'vanilla-calendar-pro/styles/index.css';

    let {
        value = null,
        onchange,
        placeholder = 'Pilih tanggal…',
        label,
        disabled = false,
        dateMin = null,
        dateMax = null,
        selectionTimeMode = 24,
        positionToInput = 'auto',
        selectedTheme = 'light',
    }: {
        value?: string | null;
        onchange?: (val: string | null) => void;
        placeholder?: string;
        label?: string;
        disabled?: boolean;
        dateMin?: string | null;
        dateMax?: string | null;
        selectionTimeMode?: false | 12 | 24;
        positionToInput?:
            'auto' | 'top' | 'bottom' | 'left' | 'right';
        selectedTheme?: 'system' | 'light' | 'dark';
    } = $props();

    let inputEl: HTMLInputElement;
    let calendar: CalendarInstance | null = null;
    let displayValue = $state('');
    let inputId = $derived(
        `vanilla-dp-${Math.random().toString(36).slice(2, 8)}`,
    );
    let repositionTimer: ReturnType<typeof setTimeout> | undefined;

    $effect(() => {
        if (value) {
            displayValue = formatDisplay(value);
        }
    });

    function formatDisplay(dateStr: string): string {
        const d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return dateStr;
        }
        let result = d.toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
        if (selectionTimeMode !== false) {
            const timePart = dateStr.split(' ')[1];
            if (timePart) {
                result += ` ${timePart}`;
            }
        }
        return result;
    }

    function parseOptions(): any {
        const opts: any = {
            inputMode: true,
            selectionTimeMode,
            positionToInput,
            selectedTheme,
            selectedDates: [],
            onInit: (self: CalendarInstance) => {
                if (value) {
                    self.set({ selectedDates: [value] });
                }
            },
            onClickDate: (self: CalendarInstance) => {
                const selected = self.context.selectedDates;
                if (selected.length > 0) {
                    const datePart = selected[0];
                    let fullValue = datePart;
                    if (selectionTimeMode !== false) {
                        const timePart = self.context.selectedTime;
                        if (timePart) {
                            fullValue += ` ${timePart}`;
                        }
                    }
                    displayValue = formatDisplay(fullValue);
                    onchange?.(fullValue);
                }
            },
            onChangeTime: (self: CalendarInstance) => {
                const selected = self.context.selectedDates;
                if (selected.length > 0) {
                    const datePart = selected[0];
                    let fullValue = datePart;
                    const timePart = self.context.selectedTime;
                    if (timePart) {
                        fullValue += ` ${timePart}`;
                    }
                    displayValue = formatDisplay(fullValue);
                    onchange?.(fullValue);
                }
            },
        };

        if (dateMin) {
            opts.dateMin = dateMin;
        }
        if (dateMax) {
            opts.dateMax = dateMax;
        }

        return opts;
    }

    onMount(() => {
        if (inputEl) {
            calendar = new Calendar(inputEl, {
                ...parseOptions(),
            });
            calendar.init();
        }

        window.addEventListener(
            'scroll',
            handleScrollReposition,
            true,
        );

        return () => {
            window.removeEventListener(
                'scroll',
                handleScrollReposition,
                true,
            );
            calendar?.destroy();
        };
    });

    function handleScrollReposition(): void {
        clearTimeout(repositionTimer);
        repositionTimer = setTimeout(positionPopup, 50);
    }

    function positionPopup(): void {
        const popup = calendar?.context.mainElement;
        if (
            !popup ||
            !inputEl ||
            !popup.hasAttribute('data-vc-input')
        ) {
            return;
        }
        if (popup.hasAttribute('data-vc-calendar-hidden')) {
            return;
        }

        const rect = inputEl.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();
        const doc = document.documentElement;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let vertical: 'bottom' | 'top';
        if (
            positionToInput === 'bottom' ||
            positionToInput === 'top'
        ) {
            vertical = positionToInput;
        } else {
            vertical =
                rect.bottom + popupRect.height <= vh
                    ? 'bottom'
                    : 'top';
        }

        let topViewport: number;
        if (vertical === 'bottom') {
            topViewport = rect.bottom;
        } else {
            topViewport = rect.top - popupRect.height;
        }

        let leftViewport = rect.left;
        if (leftViewport + popupRect.width > vw - 8) {
            leftViewport = Math.max(8, vw - 8 - popupRect.width);
        }
        if (leftViewport < 8) {
            leftViewport = 8;
        }

        popup.dataset.vcPosition = vertical;
        popup.style.top = `${topViewport + window.scrollY - doc.clientTop}px`;
        popup.style.left = `${leftViewport + window.scrollX - doc.clientLeft}px`;
    }

    function handleClear() {
        displayValue = '';
        onchange?.(null);
        calendar?.set({ selectedDates: [] });
    }
</script>

<div class="vanilla-date-picker">
    {#if label}
        <label class="form-label text-xs" for={inputId}>{label}</label
        >
    {/if}
    <div class="input-group">
        <input
            type="text"
            id={inputId}
            bind:this={inputEl}
            class={`form-control ${disabled ? 'disabled' : ''}`}
            {placeholder}
            value={displayValue}
            readonly
            {disabled}
        />
        <button
            type="button"
            class="btn btn-outline-secondary"
            disabled={disabled || !value}
            onclick={handleClear}
            aria-label="Hapus"
        >
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

<style>
    :global(.vc[data-vc-input]) {
        z-index: 2000;
    }

    .vanilla-date-picker input[readonly] {
        background-color: var(--inv-white);
        cursor: pointer;
    }

    .vanilla-date-picker input:focus {
        box-shadow: var(--inv-focus-ring);
    }

    :global(.vc) {
        --vc-bg: var(--bs-body-bg);
        --vc-color: var(--bs-body-color);
        --vc-header-color: var(--bs-body-color);
        --vc-title-color: var(--bs-body-color);
        --vc-title-color-hover: var(--bs-body-color);
        --vc-title-color-disabled: var(--bs-secondary-color);
        --vc-months-years-bg: var(--bs-tertiary-bg);
        --vc-months-years-color: var(--bs-body-color);
        --vc-months-years-color-disabled: var(--bs-secondary-color);
        --vc-months-years-bg-hover: var(--bs-secondary-bg);
        --vc-months-years-bg-selected: var(--bs-primary);
        --vc-months-years-color-selected: var(--bs-white);
        --vc-week-numbers-title-color: var(--bs-secondary-color);
        --vc-week-number-color: var(--bs-secondary-color);
        --vc-week-number-color-hover: var(--bs-body-color);
        --vc-week-day-color: var(--bs-secondary-color);
        --vc-week-day-color-hover: var(--bs-body-color);
        --vc-week-day-off-color: var(--bs-danger);
        --vc-week-day-off-color-hover: var(--bs-danger-emphasis);
        --vc-date-bg: var(--bs-body-bg);
        --vc-date-color: var(--bs-body-color);
        --vc-date-color-hover: var(--bs-body-color);
        --vc-date-bg-hover: var(--bs-secondary-bg);
        --vc-date-hover-bg: var(--bs-secondary-bg);
        --vc-date-hover-edge-bg: var(--bs-tertiary-bg);
        --vc-date-today-bg: var(--bs-primary-bg-subtle);
        --vc-date-today-color: var(--bs-primary);
        --vc-date-today-outside-color: var(--bs-secondary-color);
        --vc-date-outside-color: var(--bs-secondary-color);
        --vc-date-disabled-color: var(--bs-secondary-color);
        --vc-date-selected-bg: var(--bs-primary);
        --vc-date-selected-color: var(--bs-white);
        --vc-date-selected-outside-bg: var(--bs-secondary-color);
        --vc-date-selected-outside-color: var(--bs-white);
        --vc-date-range-middle-bg: var(--bs-primary-bg-subtle);
        --vc-date-range-middle-color: var(--bs-body-color);
        --vc-date-range-middle-outside-bg: var(--bs-secondary-bg);
        --vc-date-range-middle-outside-color: var(
            --bs-secondary-color
        );
        --vc-date-range-middle-weekend-bg: var(--bs-danger-bg-subtle);
        --vc-date-range-middle-weekend-color: var(--bs-danger);
        --vc-date-range-tooltip-bg: var(--bs-tertiary-bg);
        --vc-date-range-tooltip-color: var(--bs-secondary-color);
        --vc-date-popup-bg: var(--bs-body-bg);
        --vc-date-popup-color: var(--bs-body-color);
        --vc-date-weekend-color: var(--bs-danger);
        --vc-date-weekend-bg-hover: var(--bs-danger-bg-subtle);
        --vc-date-weekend-hover-bg: var(--bs-danger-bg-subtle);
        --vc-date-weekend-hover-edge-bg: var(--bs-danger-bg-subtle);
        --vc-date-weekend-disabled-color: var(--bs-secondary-color);
        --vc-date-weekend-disabled-outside-color: var(
            --bs-secondary-color
        );
        --vc-date-weekend-outside-bg: var(--bs-body-bg);
        --vc-date-weekend-outside-bg-hover: var(--bs-secondary-bg);
        --vc-date-weekend-outside-hover-bg: var(--bs-secondary-bg);
        --vc-date-weekend-outside-color: var(--bs-danger);
        --vc-date-weekend-outside-color-hover: var(--bs-danger);
        --vc-date-weekend-selected-bg: var(--bs-danger);
        --vc-date-weekend-selected-color: var(--bs-white);
        --vc-date-weekend-today-color: var(--bs-primary);
        --vc-date-weekend-today-disabled-color: var(
            --bs-secondary-color
        );
        --vc-date-weekend-today-outside-color: var(--bs-primary);
        --vc-time-border-color: var(--bs-secondary-border-color);
        --vc-time-input-bg: var(--bs-body-bg);
        --vc-time-input-color: var(--bs-body-color);
        --vc-time-input-bg-hover: var(--bs-secondary-bg);
        --vc-time-keeping-color: var(--bs-secondary-color);
        --vc-time-keeping-color-hover: var(--bs-body-color);
        --vc-time-separator-color: var(--bs-secondary-color);
        --vc-time-range-bg: var(--bs-primary-bg-subtle);
        --vc-time-range-track-color: var(--bs-secondary-border-color);
        --vc-time-range-thumb-bg: var(--bs-primary);
        --vc-time-range-thumb-border: var(--bs-primary);
        --vc-time-range-thumb-border-hover: var(
            --bs-primary-text-emphasis
        );
        --vc-focus-outline-color: var(--bs-primary);
    }
</style>
