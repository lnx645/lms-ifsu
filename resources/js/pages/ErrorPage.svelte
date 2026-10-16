<script lang="ts">
    import { onMount } from 'svelte';
    import { inertia } from '@inertiajs/svelte';
    import { Card, CardBody, Container } from '@sveltestrap/sveltestrap';
    import lottie, { type AnimationItem } from 'lottie-web';
    import error403 from '@/assets/error-403.json';
    import error404 from '@/assets/error-404.json';
    import error500 from '@/assets/error-500.json';
    import error503 from '@/assets/error-503.json';

    type ErrorProps = {
        status?: number;
        reason?: string;
    };

    let { status = 500, reason = '' }: ErrorProps = $props();

    let container = $state<HTMLDivElement>();
    let anim: AnimationItem | undefined;

    const messages: Record<number, { title: string; subtitle: string }> = {
        403: {
            title: 'Akses Ditolak',
            subtitle:
                'Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.',
        },
        404: {
            title: 'Halaman Tidak Ditemukan',
            subtitle:
                'Sepertinya halaman yang Anda cari tidak tersedia atau sudah dipindahkan.',
        },
        500: {
            title: 'Terjadi Kesalahan',
            subtitle:
                'Ada kendala di sisi server kami. Silakan coba beberapa saat lagi.',
        },
        503: {
            title: 'Layanan Tidak Tersedia',
            subtitle:
                'Sistem sedang dalam pemeliharaan. Mohon bersabar, kami segera kembali.',
        },
    };

    let info = $derived(
        messages[status] ?? {
            title: 'Terjadi Kesalahan',
            subtitle:
                'Terjadi sesuatu yang tidak terduga. Silakan coba kembali.',
        },
    );

    const animations: Record<number, unknown> = {
        403: error403,
        404: error404,
        500: error500,
        503: error503,
    };

    onMount(() => {
        if (!container) return;

        anim = lottie.loadAnimation({
            container,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            animationData: (animations[status] ?? error500) as object,
        });

        return () => anim?.destroy();
    });
</script>

<svelte:head>
    <title>{status} &middot; {info.title}</title>
</svelte:head>

<div class="min-vh-100 d-flex align-items-center bg-light py-5">
    <Container>
        <Card class="border-0 shadow-sm mx-auto" style="max-width: 480px;">
            <CardBody class="p-4 p-sm-5 text-center">
                <div
                    bind:this={container}
                    class="d-flex justify-content-center mb-2"
                    style="height: 220px;"
                ></div>

                <div class="display-3 fw-bold color-gradient-primary lh-1">
                    {status}
                </div>
                <h1 class="h4 fw-bold text-dark mt-2 mb-2">{info.title}</h1>
                <p class="text-muted mb-3">{info.subtitle}</p>

                {#if reason}
                    <div class="alert alert-danger small text-start mb-3">
                        {reason}
                    </div>
                {/if}

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a
                        class="btn btn-primary px-3"
                        href="/app"
                        use:inertia={{
                            prefetch: true,
                        }}
                    >
                        <i class="bi bi-house-door me-1"></i>
                        Kembali ke Beranda
                    </a>
                    <button
                        class="btn btn-outline-secondary px-3"
                        type="button"
                        onclick={() => history.back()}
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Halaman Sebelumnya
                    </button>
                </div>
            </CardBody>
        </Card>
    </Container>
</div>
