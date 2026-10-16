<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavSection,
        type AppShellNavItem,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';
    import { Alert } from '@sveltestrap/sveltestrap';
    import MataPelajaranGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import GuruPenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import GuruTugasController from '@/actions/App/Http/Controllers/Guru/TugasController';
    import GuruUjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import GuruBankSoalController from '@/actions/App/Http/Controllers/Guru/BankSoalController';
    import SiswaMateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import SiswaPenilaianController from '@/actions/App/Http/Controllers/Siswa/PenilaianController';
    import SiswaTugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';
    import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
    import type { BottomNavItem } from '@/components/BottomNav.svelte';
    let { children }: { children: Snippet } = $props();
    const authUser = $derived(usePage().props.auth?.user ?? null);
    const tahunAjaranAktif = $derived(
        usePage().props.tahunAjaranAktif ?? null,
    );
    const showTahunAjaranInfo = $derived(
        !!authUser?.siswa && !tahunAjaranAktif,
    );
    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Pengguna',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.guru
            ? 'Guru Pengajar'
            : authUser?.siswa
              ? `Siswa ${authUser.siswa.kelas?.nama ?? ''}`
              : 'Pengguna Aktif',
        homeHref: '/',
    });
    const navItems = $derived.by<(AppShellNavItem | AppShellNavSection)[]>(() => {
        if (!authUser?.gate_access) {
            return [
                {
                    href: '',
                    label: 'Dashboard',
                    icon: 'bi-grid-1x2',
                },
            ];
        }

        const items: (AppShellNavItem | AppShellNavSection)[] = [
            {
                href: DashboardController().url,
                label: 'Dashboard',
                icon: 'bi-speedometer2',
            },
        ];


        if (authUser?.guru || authUser?.role === 'guru') {
            items.push({
                section: 'Akademik',
                items: [
                    {
                        href: MataPelajaranGuruController.index().url,
                        label: 'Kelas Saya',
                        icon: 'bi-chat-dots',
                    },
                    {
                        href: GuruMateriController.index().url,
                        label: 'Materi',
                        icon: 'bi-journal-plus',
                    },
                    {
                        href: GuruTugasController.index().url,
                        label: 'Tugas',
                        icon: 'bi-clipboard-check',
                    },

                    {
                        href: GuruUjianController.index().url,
                        label: 'Ujian (CBT)',
                        icon: 'bi-pencil-square',
                    },

                    {
                        href: '/app/guru/bank-soal/kelompok',
                        label: 'Bank Soal',
                        icon: 'bi-collection',
                    },

                    {
                        href: GuruPenilaianController.index().url,
                        label: 'Penilaian',
                        icon: 'bi-award',
                    },
                ],
            } satisfies AppShellNavSection);
        }

        if (authUser?.siswa || authUser?.role === 'siswa') {
            items.push(
                {
                    href: MataPelajaranGuruController.index().url,
                    label: 'Mata Pelajaran',
                    icon: 'bi-journal-bookmark',
                },
                {
                    href: SiswaMateriController.index().url,
                    label: 'Lihat Materi',
                    icon: 'bi-book-half',
                },

                {
                    href: SiswaTugasController.index().url,
                    label: 'Tugas',
                    icon: 'bi-ui-checks-grid',
                },
                {
                    href: SiswaUjianController.index().url,
                    label: 'Ujian',
                    icon: 'bi-pencil-square',
                },
                {
                    href: SiswaPenilaianController.index().url,
                    label: 'Nilai',
                    icon: 'bi-award',
                },
            );
        }

        return items;
    });

    /**
     * Navigasi bawah khusus mobile: 5 tujuan utama per peran.
     * Guru: Dashboard, Kelas Saya, Ujian (CBT), Tugas, Materi.
     * Siswa: Dashboard, Materi, Tugas, Ujian, Nilai.
     */
    const bottomNavItems = $derived.by<BottomNavItem[]>(() => {
        if (!authUser?.gate_access) {
            return [];
        }

        const beranda: BottomNavItem = {
            href: DashboardController().url,
            label: 'Beranda',
            icon: 'bi-house-door-fill',
        };

        if (authUser?.guru || authUser?.role === 'guru') {
            return [
                beranda,
                {
                    href: MataPelajaranGuruController.index().url,
                    label: 'Kelas',
                    icon: 'bi-easel2-fill',
                },
                {
                    href: GuruUjianController.index().url,
                    label: 'Ujian',
                    icon: 'bi-pencil-square',
                },
                {
                    href: GuruTugasController.index().url,
                    label: 'Tugas',
                    icon: 'bi-clipboard-check-fill',
                },
                {
                    href: GuruMateriController.index().url,
                    label: 'Materi',
                    icon: 'bi-journal-bookmark-fill',
                },
            ];
        }

        if (authUser?.siswa || authUser?.role === 'siswa') {
            return [
                beranda,
                {
                    href: SiswaMateriController.index().url,
                    label: 'Materi',
                    icon: 'bi-book-half',
                },
                {
                    href: SiswaTugasController.index().url,
                    label: 'Tugas',
                    icon: 'bi-ui-checks-grid',
                },
                {
                    href: SiswaUjianController.index().url,
                    label: 'Ujian',
                    icon: 'bi-pencil-square',
                },
                {
                    href: SiswaPenilaianController.index().url,
                    label: 'Nilai',
                    icon: 'bi-award-fill',
                },
            ];
        }

        return [];
    });
</script>

{#snippet brandIconNode()}
    <BrandIcon />
{/snippet}

<AppShellLayout
    brandTitle="Pembelajaran"
    brandSubtitle="Modul Pembelajaran"
    {brandIconNode}
    title={authUser?.role === 'panitia' ? 'PANITIA' : authUser?.guru || authUser?.role === 'guru' ? 'GURU' : authUser?.role === 'admin' ? 'ADMIN' : 'SISWA'}
    description={authUser?.role === 'panitia' ? 'Panitia Ujian' : authUser?.guru || authUser?.role === 'guru' ? 'Manajemen Guru' : authUser?.role === 'admin' ? 'Administrator' : 'Portal Siswa'}
    {navItems}
    {user}
    {bottomNavItems}
    notifBelumDibaca={usePage().props.notifBelumDibaca ?? 0}
>
    {#if showTahunAjaranInfo}
        <Alert
            color="info"
            class="d-flex align-items-center gap-2 mb-3"
        >
            <i class="bi bi-info-circle-fill"></i>
            <span>Tahun Pelajaran Baru Belum dimulai</span>
        </Alert>
    {/if}
    {@render children?.()}
</AppShellLayout>
