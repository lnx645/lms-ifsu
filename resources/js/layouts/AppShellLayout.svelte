<script lang="ts">
    import { CookieBox } from 'svelte-cookie-consent';
    import {
        Container,
        Dropdown,
        DropdownToggle,
        DropdownMenu,
        DropdownItem,
    } from '@sveltestrap/sveltestrap';
    import type { Snippet } from 'svelte';
    import { onMount } from 'svelte';
    import { Toaster } from 'svelte-sonner';
    import { inertia, page, router } from '@inertiajs/svelte';
    import '@/styles/modules/app-shell-layout.scss';
    import AuthenticatedSessionController from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import TahunAjaranInfo from '@/components/TahunAjaranInfo.svelte';
    import BottomNav, {
        type BottomNavItem,
    } from '@/components/BottomNav.svelte';

    export type AppShellNavItem = {
        href?: string;
        label: string;
        icon?: string;
        badge?: string;
        children?: AppShellNavItem[];
    };

    export type AppShellNavSection = {
        section: string;
        icon?: string;
        items: AppShellNavItem[];
    };

    export type AppShellUser = {
        name: string;
        email?: string;
        id?: string | number;
        role: string;
        homeHref?: string;
    };

    let {
        children,
        brandTitle = 'GATEWAY',
        brandSubtitle = 'Portal Modul',
        brandIcon = 'bi-shield-lock-fill',
        brandIconNode,
        navItems = [],
        user = { name: 'Pengguna', email: '', id: '', role: '' },
        title = 'Dashboard',
        description = '',
        bottomNavItems = [],
        notifBelumDibaca = 0,
    }: {
        children: Snippet;
        brandTitle?: string;
        brandSubtitle?: string;
        brandIcon?: string;
        brandIconNode?: Snippet;
        navItems?: Array<AppShellNavItem | AppShellNavSection>;
        user?: AppShellUser;
        title?: string;
        description?: string;
        /** Item navigasi bawah khusus mobile (< 992px). Kosong = tidak tampil. */
        bottomNavItems?: BottomNavItem[];
        /** Jumlah notifikasi belum dibaca (dari shared prop). */
        notifBelumDibaca?: number;
    } = $props();

    let sidebarOpen = $state(false);
    let sidebarCollapsed = $state(false);
    let hoverExpand = $state(false);
    let isDesktop = $state(false);
    let hoverLeaveTimer: ReturnType<typeof setTimeout> | null = null;
    let theme = $state<'light' | 'dark'>('light');

    onMount(() => {
        sidebarCollapsed =
            localStorage.getItem('app-sidebar-collapsed') === 'true';

        const saved = localStorage.getItem('theme') as 'light' | 'dark' | null;
        theme = saved ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', theme);

        const mq = window.matchMedia('(min-width: 992px)');
        isDesktop = mq.matches;
        const handler = (event: MediaQueryListEvent) => {
            isDesktop = event.matches;
        };
        mq.addEventListener('change', handler);

        return () => mq.removeEventListener('change', handler);
    });

    $effect(() => {
        localStorage.setItem(
            'app-sidebar-collapsed',
            String(sidebarCollapsed),
        );
    });

    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
    }

    function toggleSidebarCollapse() {
        sidebarCollapsed = !sidebarCollapsed;
    }

    function toggleTheme() {
        theme = theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
    }

    let sidebarMini = $derived(sidebarCollapsed && !hoverExpand);

    function openSidebarHover() {
        if (!isDesktop) {
            return;
        }

        if (hoverLeaveTimer) {
            clearTimeout(hoverLeaveTimer);
        }

        hoverExpand = true;
    }

    function scheduleCollapse() {
        if (!isDesktop) {
            return;
        }

        if (hoverLeaveTimer) {
            clearTimeout(hoverLeaveTimer);
        }

        hoverLeaveTimer = setTimeout(() => {
            hoverExpand = false;
            hoverLeaveTimer = null;
        }, 180);
    }

    function navigate() {
        sidebarOpen = false;
    }

    function normalizeUrl(url: string): string {
        return (
            url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/'
        );
    }

    // Only ONE item should be highlighted. Pick the most specific (longest)
    // matching href so a parent path (e.g. /m/pembelajaran) does not also
    // light up when a child (e.g. /m/pembelajaran/mata-pelajaran) is active.
    let activeHref = $derived.by<string | null>(() => {
        const current = normalizeUrl(page.url);
        let best: string | null = null;
        const consider = (href?: string) => {
            if (!href) return;
            const target = normalizeUrl(href);
            if (
                current === target ||
                current.startsWith(target + '/')
            ) {
                if (
                    best === null ||
                    target.length > normalizeUrl(best).length
                ) {
                    best = href;
                }
            }
        };
        for (const item of navItems) {
            if ('section' in item) {
                for (const child of item.items ?? []) {
                    consider(child.href);
                }
                continue;
            }
            consider(item.href);
            for (const child of item.children ?? []) {
                consider(child.href);
            }
        }
        return best;
    });

    function isActive(href: string) {
        return activeHref === href;
    }

    function groupHasActiveChild(
        item: AppShellNavItem | AppShellNavSection,
    ): boolean {
        if ('section' in item) {
            return false;
        }
        return (item.children ?? []).some(
            (child) => child.href != null && isActive(child.href),
        );
    }

    let openGroups = $state<Record<string, boolean>>({});

    $effect(() => {
        for (const item of navItems) {
            if (!('section' in item) && groupHasActiveChild(item)) {
                openGroups[item.label] = true;
            }
        }
    });

    function toggleGroup(label: string) {
        openGroups[label] = !openGroups[label];
    }

    function logout() {
        router.post(AuthenticatedSessionController.destroy().url, {}, {
            onFinish: () => {
                sidebarOpen = false;
            },
        });
    }

    let userInitials = $derived(
        user.name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part: string) => part.charAt(0).toUpperCase())
            .join(''),
    );

    let userMenuOpen = $state(false);
</script>

<Toaster richColors position="top-right" />

<div class="app-shell">
    <!-- Sidebar -->
    <aside
        class={`app-shell__sidebar ${sidebarOpen ? 'open' : ''} ${sidebarMini ? 'collapsed' : ''} ${hoverExpand ? 'app-shell__sidebar--hover' : ''}`}
        onmouseenter={openSidebarHover}
        onmouseleave={scheduleCollapse}
    >
        <div class="app-shell__brand">
            <div class="app-shell__brand-badge">
                {#if brandIconNode}
                    {@render brandIconNode()}
                {:else}
                    <i class="bi {brandIcon}"></i>
                {/if}
            </div>
            <div class="app-shell__brand-text">
                <h5 class="app-shell__brand-title">{brandTitle}</h5>
                <span class="app-shell__brand-subtitle"
                    >{brandSubtitle}</span
                >
            </div>
        </div>

        {#snippet navLink(entry: AppShellNavItem, subitem: boolean)}
            <a
                use:inertia={{
                    prefetch: false,
                }}
                href={entry.href}
                title={entry.label}
                onclick={navigate}
                class={`app-shell__nav-item ${subitem ? 'app-shell__nav-subitem' : ''} ${isActive(entry.href ?? '') ? 'active' : ''}`}
                aria-current={isActive(entry.href ?? '')
                    ? 'page'
                    : undefined}
            >
                <i class="bi {entry.icon}"></i>
                <span>{entry.label}</span>
                {#if entry.badge}
                    <span class="app-shell__nav-badge"
                        >{entry.badge}</span
                    >
                {/if}
            </a>
        {/snippet}

        <nav class="app-shell__nav">
            {#each navItems as item ('section' in item ? item.section : (item.href ?? item.label))}
                {#if 'section' in item}
                    <div class="app-shell__nav-category">
                        {item.section}
                    </div>
                    {#each item.items as child (child.href ?? child.label)}
                        {@render navLink(child, true)}
                    {/each}
                {:else if item.children?.length}
                    <button
                        type="button"
                        title={item.label}
                        class={`app-shell__nav-item app-shell__nav-group ${openGroups[item.label] ? 'is-open' : ''}`}
                        onclick={() => toggleGroup(item.label)}
                        aria-expanded={openGroups[item.label]}
                    >
                        <i class="bi {item.icon}"></i>
                        <span class="app-shell__nav-group-label"
                            >{item.label}</span
                        >
                        {#if item.badge}
                            <span class="app-shell__nav-badge"
                                >{item.badge}</span
                            >
                        {/if}
                        <i
                            class={`bi bi-chevron-down app-shell__nav-group-caret`}
                        ></i>
                    </button>
                    <div
                        class={`app-shell__nav-group-collapse ${openGroups[item.label] ? 'is-expanded' : ''}`}
                    >
                        <div class="app-shell__nav-group-inner">
                            {#each item.children as child (child.href)}
                                {@render navLink(child, true)}
                            {/each}
                        </div>
                    </div>
                {:else}
                    {@render navLink(item, false)}
                {/if}
            {/each}
        </nav>

        <div class="app-shell__sidebar-footer">
            <button
                type="button"
                title="Keluar Sesi"
                class={`app-shell__nav-item app-shell__nav-item--danger`}
                onclick={logout}
            >
                <i class="bi bi-box-arrow-left"></i>
                <span>Keluar Sesi</span>
            </button>
        </div>
    </aside>

    {#if sidebarOpen}
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div class="app-shell__overlay" onclick={toggleSidebar}></div>
    {/if}

    <!-- Main Content Wrapper -->
    <div class="app-shell__main">
        <!-- Header -->
        <header class="app-shell__header">
            <div class="app-shell__header-left">
                <button
                    class="app-shell__menu-toggle"
                    onclick={toggleSidebar}
                    aria-label="Buka menu navigasi"
                >
                    <i class="bi bi-list"></i>
                </button>
                <button
                    class={`app-shell__collapse-toggle ${sidebarCollapsed ? 'is-active' : ''}`}
                    onclick={toggleSidebarCollapse}
                    onmouseenter={openSidebarHover}
                    onmouseleave={scheduleCollapse}
                    aria-label={sidebarCollapsed
                        ? 'Perlihatkan bilah sisi'
                        : 'Ciutkan bilah sisi'}
                    title={sidebarCollapsed
                        ? 'Perlihatkan bilah sisi'
                        : 'Ciutkan bilah sisi'}
                >
                    <i
                        class={`bi ${sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'}`}
                    ></i>
                </button>
                <div class="app-shell__header-title-wrapper">
                    <h1 class="app-shell__header-title">{title}</h1>
                    {#if description}
                        <p class="app-shell__header-desc">
                            {description}
                        </p>
                    {/if}
                </div>
            </div>

            <div class="app-shell__header-right">
                {#if user.role === 'siswa'}
                    <a
                        href="/app/notifikasi"
                        class="app-shell__theme-toggle position-relative"
                        aria-label="Notifikasi"
                        title="Notifikasi"
                    >
                        <i class="bi bi-bell-fill"></i>
                        {#if notifBelumDibaca > 0}
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem">
                                {notifBelumDibaca > 9 ? '9+' : notifBelumDibaca}
                            </span>
                        {/if}
                    </a>
                {/if}
                <button
                    class="app-shell__theme-toggle"
                    onclick={toggleTheme}
                    aria-label={theme === 'dark' ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'}
                    title={theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'}
                >
                    <i class="bi {theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'}"></i>
                </button>
                <Dropdown
                    direction="down"
                    class="app-shell__user-dropdown"
                    isOpen={userMenuOpen}
                    toggle={() => (userMenuOpen = !userMenuOpen)}
                >
                    <DropdownToggle
                        nav
                        class={`app-shell__user-toggle d-flex align-items-center gap-2 ${userMenuOpen ? 'is-open' : ''}`}
                        aria-label="Menu pengguna"
                    >
                        <span class="app-shell__user-avatar"
                            >{userInitials}</span
                        >
                        <span class="app-shell__user-meta">
                            <span class="app-shell__user-name"
                                >{user.name}</span
                            >
                            {#if user.role}
                                <span
                                    class={`app-shell__user-role app-shell__user-role--${user.role}`}
                                    >{user.role}</span
                                >
                            {/if}
                        </span>
                        <i
                            class={`bi bi-chevron-down app-shell__user-caret ${userMenuOpen ? 'app-shell__user-caret--open' : ''}`}
                        ></i>
                    </DropdownToggle>

                    <DropdownMenu
                        end
                        class={`app-shell__user-menu p-0 overflow-hidden`}
                        style="width: 264px;"
                    >
                        <!-- Bagian Header Dropdown (Biru) -->
                        <div class="app-shell__menu-profile-header">
                            <div class="app-shell__menu-profile-text">
                                <div
                                    class="app-shell__menu-profile-name"
                                >
                                    {user.name}
                                </div>
                                {#if user.role}
                                    <span
                                        class="app-shell__role-badge"
                                        >{user.role}</span
                                    >
                                {/if}
                                {#if user.email}
                                    <div
                                        class="app-shell__menu-profile-email"
                                    >
                                        {user.email}
                                    </div>
                                {/if}
                            </div>
                        </div>

                        <div class="app-shell__menu-links">
                            <a
                                use:inertia
                                href={user.homeHref ?? '/'}
                                class="app-shell__custom-dropdown-item"
                                onclick={() => {
                                    userMenuOpen = false;
                                    navigate();
                                }}
                            >
                                <i class="bi bi-house-door"></i>
                                <span>Beranda</span>
                            </a>
                        </div>

                        <DropdownItem
                            divider
                            class="app-shell__menu-divider"
                        />

                        <!-- Tombol Keluar -->
                        <div class="app-shell__menu-footer">
                            <button
                                type="button"
                                class={`app-shell__custom-dropdown-item app-shell__logout-btn`}
                                onclick={logout}
                            >
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Keluar Sesi</span>
                            </button>
                        </div>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </header>

        <!-- Body Content -->
        <main
            class="app-shell__content p-md-2 p-3 px-2"
            class:app-shell__content--with-bottom-nav={bottomNavItems.length >
                0}
        >
            <Container fluid class="px-0">
                {@render children?.()}
            </Container>
        </main>
    </div>
</div>

{#if bottomNavItems.length > 0}
    <BottomNav items={bottomNavItems} />
{/if}

<ConfirmDialog />
