<script lang="ts">
    import { CookieBox } from 'svelte-cookie-consent';

    // STANDAR INDUSTRI: Semua cookie non-esensial default-nya false (Opt-in)
    const choices = $state({
        necessary: {
            label: 'Cookie Esensial',
            description:
                'Diperlukan untuk keamanan dan fungsi dasar situs. Tidak dapat dimatikan.',
            value: true,
            mandatory: true,
        },
        analytics: {
            label: 'Cookie Analitik',
            description:
                'Digunakan untuk memahami bagaimana pengunjung berinteraksi dengan situs web.',
            value: false, // <-- Diubah jadi false
        },
        marketing: {
            label: 'Cookie Pemasaran',
            description:
                'Digunakan untuk melacak pengunjung di seluruh situs web guna menampilkan iklan yang relevan.',
            value: false, // <-- Diubah jadi false
        },
        tracking: {
            label: 'Cookie Pelacakan',
            description:
                'Digunakan untuk analisis lanjutan dan personalisasi pengalaman pengguna.',
            value: false, // <-- Diubah jadi false
        },
    });
</script>

<CookieBox
    cookie={{
        name: 'app_cookie_consent',
        path: '/',
        secure: true,
        sameSite: 'strict',
    }}
    bgColor={'white'}
    fgColor="black"
    heading="Pengaturan Privasi & Cookie"
    description="Kami menggunakan cookie untuk meningkatkan pengalaman menjelajah Anda, menganalisis lalu lintas situs, dan mempersonalisasi konten. Anda dapat memilih untuk mengizinkan atau menolak kategori cookie tertentu di bawah ini."
    acceptAllLabel="Terima Semua"
    rejectAllLabel="Tolak Semua"
    customize={{
        label: 'Sesuaikan Pengaturan',
        chooseLabel: 'Pilih Cookie yang Ingin Diaktifkan',
        confirmLabel: 'Simpan Pilihan Saya',
    }}
    {choices}
/>
