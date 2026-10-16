<script lang="ts">
    import lottie, { type AnimationItem } from 'lottie-web';
    import { confirm } from '@/lib/confirm.svelte';
    import { Modal, ModalBody, ModalFooter, Button } from '@sveltestrap/sveltestrap';
    import warningAnim from '@/assets/warning-animate.json';

    let animEl = $state<HTMLDivElement>();
    let anim: AnimationItem | undefined;

    $effect(() => {
        if (confirm.isOpen && animEl) {
            anim = lottie.loadAnimation({
                container: animEl,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                animationData: warningAnim,
            });

            return () => {
                anim?.destroy();
                anim = undefined;
            };
        }
    });
</script>

<Modal isOpen={confirm.isOpen} toggle={confirm._cancel} centered>
    <ModalBody class="text-center py-4">
        <div
            bind:this={animEl}
            class="mb-3"
            style="width: 120px; height: 120px; margin: 0 auto;"
        ></div>
        <h5 class="mb-1">{confirm.current.title ?? 'Konfirmasi'}</h5>
        <p class="text-muted mb-0">{confirm.current.message ?? ''}</p>
    </ModalBody>
    <ModalFooter class="justify-content-center border-0">
        <Button color="secondary" outline onclick={confirm._cancel}>
            {confirm.current.cancelText ?? 'Batal'}
        </Button>
        <Button color={confirm.current.color ?? 'danger'} onclick={confirm._accept}>
            {confirm.current.confirmText ?? 'Ya'}
        </Button>
    </ModalFooter>
</Modal>
