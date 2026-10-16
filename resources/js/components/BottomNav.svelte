<script lang="ts">
    import { inertia, page } from '@inertiajs/svelte';

    export type BottomNavItem = {
        href: string;
        label: string;
        icon: string;
        badge?: string | number;
    };

    let { items = [] }: { items: BottomNavItem[] } = $props();

    function normalizeUrl(url: string): string {
        return (
            url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/'
        );
    }

    let activeHref = $derived.by<string | null>(() => {
        const current = normalizeUrl(page.url);
        let best: string | null = null;
        for (const item of items) {
            const target = normalizeUrl(item.href);
            if (
                current === target ||
                current.startsWith(target + '/')
            ) {
                if (best === null || target.length > normalizeUrl(best).length) {
                    best = item.href;
                }
            }
        }
        return best;
    });
</script>

{#if items.length > 0}
    <nav class="bottom-nav" aria-label="Navigasi utama">
        {#each items as item (item.href)}
            {@const active = activeHref === item.href}
            <a
                use:inertia={{ prefetch: false }}
                href={item.href}
                class="bottom-nav__item"
                class:active
                aria-current={active ? 'page' : undefined}
            >
                <span class="bottom-nav__icon">
                    <i class="bi {item.icon}"></i>
                    {#if item.badge}
                        <span class="bottom-nav__badge">{item.badge}</span>
                    {/if}
                </span>
                <span class="bottom-nav__label">{item.label}</span>
            </a>
        {/each}
    </nav>
{/if}

<style>
    .bottom-nav {
        display: none;
    }

    @media (max-width: 991.98px) {
        .bottom-nav {
            display: flex;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1040;
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        .bottom-nav__item {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.15rem;
            padding: 0.6rem 0.25rem 0.55rem;
            color: var(--bs-secondary-color);
            text-decoration: none;
            min-height: 60px;
            position: relative;
            transition: color 0.15s ease;
        }

        .bottom-nav__item:active {
            background: var(--bs-tertiary-bg);
        }

        .bottom-nav__icon {
            position: relative;
            font-size: 1.35rem;
            line-height: 1;
            display: flex;
        }

        .bottom-nav__label {
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bottom-nav__item.active {
            color: var(--bs-primary);
        }

        .bottom-nav__item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2.2rem;
            height: 3px;
            border-radius: 0 0 4px 4px;
            background: var(--bs-primary);
        }

        .bottom-nav__badge {
            position: absolute;
            top: -6px;
            right: -10px;
            min-width: 1.1rem;
            height: 1.1rem;
            padding: 0 0.25rem;
            border-radius: 999px;
            background: var(--bs-danger);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
    }
</style>
