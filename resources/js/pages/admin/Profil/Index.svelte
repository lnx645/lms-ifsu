<script lang="ts">
    import { untrack } from 'svelte';
    import { Badge, Button, Card, CardBody, FormGroup, Input, Label } from '@sveltestrap/sveltestrap';
    import { useForm } from '@inertiajs/svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';
    import ProfilController from '@/actions/App/Http/Controllers/Admin/ProfilController';

    interface ProfilData {
        id: number;
        name: string;
        email: string;
        role: string;
        google_id: string | null;
        created_at: string | null;
        guru?: {
            nip: string | null;
            nama_lengkap: string;
            pendidikan_terakhir: string | null;
            jenis_kelamin: string | null;
            foto_profil: string | null;
        } | null;
        siswa?: {
            nisn: string;
            nis: string | null;
            nama_lengkap: string;
            kelas: string | null;
            jenis_kelamin: string | null;
            foto_profil: string | null;
        } | null;
    }

    let { profil }: { profil: ProfilData } = $props();

    const foto = $derived(profil.guru?.foto_profil ?? profil.siswa?.foto_profil ?? null);
    const nama = $derived(profil.guru?.nama_lengkap ?? profil.siswa?.nama_lengkap ?? profil.name);
    const roleLabel = $derived(
        profil.role === 'admin' ? 'Administrator' : profil.role === 'guru' ? 'Guru' : 'Siswa',
    );

    const form = useForm(
        untrack(() => ({
            name: profil.name,
            email: profil.email,
            password: '',
            password_confirmation: '',
        })),
    );

    function submit() {
        const route = ProfilController.update();
        form.submit({ url: route.url, method: route.method });
    }

    const details = $derived(
        profil.guru
            ? [
                  { label: 'NIP', value: profil.guru?.nip ?? '-' },
                  { label: 'Pendidikan Terakhir', value: profil.guru?.pendidikan_terakhir ?? '-' },
                  {
                      label: 'Jenis Kelamin',
                      value:
                          profil.guru?.jenis_kelamin === 'L'
                              ? 'Laki-laki'
                              : profil.guru?.jenis_kelamin === 'P'
                                ? 'Perempuan'
                                : '-',
                  },
              ]
            : profil.siswa
              ? [
                    { label: 'NISN', value: profil.siswa?.nisn ?? '-' },
                    { label: 'NIS', value: profil.siswa?.nis ?? '-' },
                    { label: 'Kelas', value: profil.siswa?.kelas ?? '-' },
                    {
                        label: 'Jenis Kelamin',
                        value:
                            profil.siswa?.jenis_kelamin === 'L'
                                ? 'Laki-laki'
                                : profil.siswa?.jenis_kelamin === 'P'
                                  ? 'Perempuan'
                                  : '-',
                    },
                ]
              : [],
    );
</script>

<PageHeader title="Profil Saya" subtitle="Informasi akun dan identitas pengguna." />

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <Card class="border rounded-1 shadow-sm h-100">
            <CardBody class="text-center p-4">
                <Avatar src={foto} name={nama} size={96} />
                <h2 class="h5 fw-semibold mb-1 mt-3">{nama}</h2>
                <p class="text-muted small mb-2">{profil.email}</p>
                <Badge color={profil.role === 'admin' ? 'danger' : 'primary'} pill>
                    {roleLabel}
                </Badge>
                {#if profil.google_id}
                    <p class="small text-secondary mt-3 mb-0">
                        <i class="bi bi-google me-1"></i>Login via Google
                    </p>
                {/if}
                {#if profil.created_at}
                    <p class="small text-secondary mb-0">
                        <i class="bi bi-calendar3 me-1"></i>Terdaftar sejak {profil.created_at}
                    </p>
                {/if}
            </CardBody>
        </Card>
    </div>

    <div class="col-12 col-lg-8">
        <Card class="border rounded-1 shadow-sm mb-3">
            <CardBody>
                <h3 class="h6 fw-semibold mb-3">
                    <i class="bi bi-person-badge me-2 text-primary"></i>Identitas
                </h3>
                {#if details.length > 0}
                    <dl class="row mb-0">
                        {#each details as detail (detail.label)}
                            <dt class="col-sm-4 text-secondary fw-normal">{detail.label}</dt>
                            <dd class="col-sm-8 fw-semibold">{detail.value}</dd>
                        {/each}
                    </dl>
                {:else}
                    <p class="text-muted small mb-0">Tidak ada data identitas tambahan.</p>
                {/if}
            </CardBody>
        </Card>

        <Card class="border rounded-1 shadow-sm">
            <CardBody>
                <h3 class="h6 fw-semibold mb-3">
                    <i class="bi bi-gear me-2 text-primary"></i>Pengaturan Akun
                </h3>
                <form onsubmit={(e) => { e.preventDefault(); submit(); }}>
                    <FormGroup>
                        <Label for="name">Nama</Label>
                        <Input id="name" type="text" bind:value={form.name} invalid={!!form.errors.name} />
                        {#if form.errors.name}
                            <small class="text-danger d-block mt-1">{form.errors.name}</small>
                        {/if}
                    </FormGroup>
                    <FormGroup>
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" bind:value={form.email} invalid={!!form.errors.email} />
                        {#if form.errors.email}
                            <small class="text-danger d-block mt-1">{form.errors.email}</small>
                        {/if}
                    </FormGroup>
                    <FormGroup>
                        <Label for="password">Password Baru <span class="text-secondary small">(kosongkan jika tidak diganti)</span></Label>
                        <Input id="password" type="password" bind:value={form.password} invalid={!!form.errors.password} />
                        {#if form.errors.password}
                            <small class="text-danger d-block mt-1">{form.errors.password}</small>
                        {/if}
                    </FormGroup>
                    <FormGroup>
                        <Label for="password_confirmation">Konfirmasi Password Baru</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            bind:value={form.password_confirmation}
                            invalid={!!form.errors.password_confirmation}
                        />
                        {#if form.errors.password_confirmation}
                            <small class="text-danger d-block mt-1">{form.errors.password_confirmation}</small>
                        {/if}
                    </FormGroup>
                    <Button color="primary" disabled={form.processing}>
                        <i class="bi bi-check2 me-1"></i> Simpan Perubahan
                    </Button>
                </form>
            </CardBody>
        </Card>
    </div>
</div>
