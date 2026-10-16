import { createInertiaApp } from '@inertiajs/svelte';
import MainLayout from '@/layouts/MainLayout.svelte';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/dash-clean.css';
import { initializeFlashToast } from './lib/flash-toast.svelte';
import AdminLayout from './layouts/admin/AdminLayout.svelte';
import UserLayout from './layouts/app/UserLayout.svelte';
import ExamLayout from './layouts/ExamLayout.svelte';
import ErrorPage from './pages/ErrorPage.svelte';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout(name, page) {
        if (name.startsWith('ErrorPage')) {
            return ErrorPage;
        }
        // Halaman ujian terpisah (/ujian/*): tanpa layout sama sekali,
        // halaman sudah full-page dengan header sendiri.
        if (name.startsWith('ujian/')) {
            return undefined;
        }
        // Halaman kerjakan siswa: layout independen tanpa sidebar/nav agar fokus.
        if (name === 'siswa/Ujian/Kerjakan') {
            return ExamLayout;
        }
        let url = page.url;
        if (url.startsWith('/admin')) {
            return AdminLayout;
        } else if (url.startsWith('/app')) {
            return UserLayout;
        }
        return MainLayout;
    },
    progress: {
        color: 'var(--inv-primary-500)',
    },
});

initializeFlashToast();
