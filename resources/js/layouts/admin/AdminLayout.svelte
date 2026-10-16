<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavItem,
        type AppShellNavSection,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';
    import DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
    import JurusanController from '@/actions/App/Http/Controllers/Admin/JurusanController';
    import MatpelController from '@/actions/App/Http/Controllers/Admin/MatpelController';
    import TahunAjaranController from '@/actions/App/Http/Controllers/Admin/TahunAjaranController';
    import KelasController from '@/actions/App/Http/Controllers/Admin/KelasController';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import SiswaController from '@/actions/App/Http/Controllers/Admin/SiswaController';
    import ProfilController from '@/actions/App/Http/Controllers/Admin/ProfilController';
    import AkunAdminController from '@/actions/App/Http/Controllers/Admin/AkunAdminController';
    import NaikKelasController from '@/actions/App/Http/Controllers/Admin/NaikKelasController';
import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
import LaporanController from '@/actions/App/Http/Controllers/Admin/LaporanController';
import UjianController from '@/actions/App/Http/Controllers/Admin/UjianController';
import PeriodeUjianController from '@/actions/App/Http/Controllers/Admin/PeriodeUjianController';
import RuangController from '@/actions/App/Http/Controllers/Admin/RuangController';
import BankSoalController from '@/actions/App/Http/Controllers/Admin/BankSoalController';

    let {
        children,
        tahunAjaranAktif,
    }: { children: Snippet; tahunAjaranAktif: { name: string } } =
        $props();

    const authUser = $derived(usePage().props.auth?.user ?? null);

    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Administrator',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.role ? authUser.role : 'Administrator',
        homeHref: DashboardController.__invoke().url,
    });

    const navItems: Array<AppShellNavItem | AppShellNavSection> = [
        {
            href: DashboardController.__invoke().url,
            label: 'Dashboard',
            icon: 'bi-speedometer2',
        },
        {
            section: 'Data Master',
            items: [
                {
                    href: PengajarController.index().url,
                    label: 'Pengajar',
                    icon: 'bi-people-fill',
                },
                {
                    href: SiswaController.index().url,
                    label: 'Peserta Didik',
                    icon: 'bi-mortarboard-fill',
                },

                {
                    href: KelasController.index().url,
                    label: 'Kelas',
                    icon: 'bi-collection-fill',
                },
                {
                    href: PenilaianController.index().url,
                    label: 'Penilaian',
                    icon: 'bi-card-checklist',
                },
                {
                    href: NaikKelasController.index().url,
                    label: 'Naik Kelas',
                    icon: 'bi-arrow-up-circle-fill',
                },
            ],
        },


        {
            section: 'Account',
            items: [
                {
                    href: ProfilController.index().url,
                    label: 'Profil',
                    icon: 'bi-person-circle',
                },
                {
                    href: AkunAdminController.index().url,
                    label: 'Akun Admin',
                    icon: 'bi-shield-lock',
                },
            ],
        },
        {
            section: 'Laporan',
            items: [
                {
                    href: LaporanController.index().url,
                    label: 'Cetak Laporan',
                    icon: 'bi-printer-fill',                },
            ],
        },
        {
            href: '#',
            label: 'Pengaturan',
            icon: 'bi-gear-fill',
            children: [
                {
                    label: 'Pengaturan Jurusan',
                    href: JurusanController.index().url,
                    icon: 'bi-diagram-3-fill',
                },
                {
                    label: 'Pengaturan Matpel',
                    href: MatpelController.index().url,
                    icon: 'bi-book-half',
                },
                {
                    label: 'Pengaturan Tahun Ajaran',
                    href: TahunAjaranController.index().url,
                    icon: 'bi-calendar',
                },
            ],
        },
    ];
</script>

{#snippet brandIconNode()}
    <BrandIcon />
{/snippet}

<AppShellLayout
    brandTitle="ADMIN"
    brandSubtitle="Panel Manajemen"
    {brandIconNode}
    title="Panel Administrasi"
    description={`TA${tahunAjaranAktif?.name}`}
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
