<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, ListGroup, ListGroupItem } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Notifikasi from '@/actions/App/Http/Controllers/Siswa/NotifikasiController';

    type Item = {
        id: number; tipe: string; judul: string; pesan: string | null;
        tautan: string | null; dibaca: boolean; waktu: string;
    };
    let { notifikasis }: { notifikasis: Item[] } = $props();

    function buka(n: Item) {
        router.post(Notifikasi.baca.url({ notifikasi: n.id }));
    }
    function bacaSemua() {
        router.post(Notifikasi.bacaSemua.url());
    }

    const warnaTipe: Record<string, string> = { jadwal: 'primary', nilai: 'success', pengumuman: 'warning', info: 'secondary' };
</script>

<PageHeader title="Notifikasi" />

<Card>
    <CardHeader class="d-flex justify-content-between align-items-center">
        <span>Notifikasi ({notifikasis.filter((n) => !n.dibaca).length} belum dibaca)</span>
        <Button size="sm" color="outline-secondary" onclick={bacaSemua}>Tandai semua dibaca</Button>
    </CardHeader>
    <CardBody class="p-0">
        {#if notifikasis.length === 0}
            <div class="p-3"><EmptyState message="Belum ada notifikasi" hint="Notifikasi jadwal ujian dan nilai akan muncul di sini." /></div>
        {:else}
            <ListGroup flush>
                {#each notifikasis as n}
                    <ListGroupItem
                        action
                        onclick={() => buka(n)}
                        class={n.dibaca ? '' : 'fw-bold'}
                        style={!n.dibaca ? 'background:var(--bs-primary-bg-subtle)' : ''}
                    >
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <Badge color={warnaTipe[n.tipe] ?? 'secondary'} class="me-2">{n.tipe}</Badge>
                                {n.judul}
                                {#if n.pesan}<div class="small text-muted fw-normal mt-1">{n.pesan}</div>{/if}
                            </div>
                            <small class="text-muted text-nowrap">{n.waktu}</small>
                        </div>
                    </ListGroupItem>
                {/each}
            </ListGroup>
        {/if}
    </CardBody>
</Card>
