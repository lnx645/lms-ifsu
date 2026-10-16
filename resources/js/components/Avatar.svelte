<script lang="ts">
    let {
        src = null,
        name = '',
        size = 40,
        rounded = 'circle',
    }: {
        src?: string | null;
        name?: string;
        size?: number;
        rounded?: 'circle' | 'rounded';
    } = $props();

    const photo = $derived(src ? `/storage/${src}` : null);

    const initials = $derived(
        name
            ? name
                  .split(',')[0]
                  .split(' ')
                  .slice(0, 2)
                  .map((w: string) => w[0])
                  .join('')
                  .toUpperCase()
            : '?',
    );

    const styles = $derived(
        `width:${size}px;height:${size}px;font-size:${Math.max(10, size * 0.38)}px`,
    );

    const shapeClass = $derived(
        rounded === 'circle' ? 'rounded-circle' : 'rounded',
    );
</script>

{#if photo}
    <img
        src={photo}
        alt={name}
        class={`object-fit-cover bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center fw-semibold flex-shrink-0 ${shapeClass}`}
        style={styles}
    />
{:else}
    <span
        class={`bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center fw-semibold flex-shrink-0 ${shapeClass}`}
        style={styles}
        aria-label="Avatar {name}"
        >{initials}</span
    >
{/if}
