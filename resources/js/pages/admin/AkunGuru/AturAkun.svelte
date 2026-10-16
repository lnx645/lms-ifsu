<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import {
        FormGroup,
        Label,
        Input,
        Button,
        Badge,
        NavItem,
    } from '@sveltestrap/sveltestrap';
    import AkunGuruController from '@/actions/App/Http/Controllers/Admin/AkunGuruController';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';

    type Guru = {
        id: number;
        nama_lengkap: string;
        nip: string | null;
        jenis_kelamin: string | null;
        foto_profil: string | null;
        is_aktif: boolean;
    };

    type User = {
        id: number;
        name: string;
        email: string;
    } | null;

    let { guru, user }: { guru?: Guru; user?: User } = $props();

    const form = useForm({
        name: user?.name ?? guru?.nama_lengkap,
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
    });

    const isCreating = $derived(!user);

    function handleSubmit() {
        if (isCreating) {
            form.submit(AkunGuruController.store({ guru: guru?.id as number }));
        } else {
            form.submit(
                AkunGuruController.update({ guru: guru?.id as number }),
            );
        }
    }

    function handleDelete() {
        if (
            !confirm(
                `Hapus akun ${user?.name ?? guru?.nama_lengkap}?\nIni akan menghapus akun pengguna secara permanen.`,
            )
        ) {
            return;
        }
        router.delete(
            AkunGuruController.destroy({ guru: guru?.id as number }).url,
        );
    }
</script><div class="container-fluid py-4">
    <PageHeader
        title="Atur Akun Guru"
        subtitle={`Kelola akun pengguna untuk ${guru?.nama_lengkap ?? '-'}`}
    >
        {#snippet actions()}
            {#if user}
                <Button color="danger" size="sm" onclick={handleDelete}>
                    <i class="bi bi-trash me-1"></i> Hapus Akun
                </Button>
            {/if}
            <Button
                color="primary"
                size="sm"
                onclick={handleSubmit}
                disabled={form.processing}
            >
                <i class="bi {isCreating ? 'bi-plus-lg' : 'bi-save'} me-1"></i>
                {isCreating ? 'Buat Akun' : 'Simpan Perubahan'}
            </Button>
        {/snippet}
    </PageHeader>

    {#if guru}
        <div class="card border rounded-1 shadow-sm mb-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <Avatar
                    src={guru?.foto_profil}
                    name={guru?.nama_lengkap ?? ''}
                    size={56}
                />
                <div>
                    <div class="fw-semibold text-body">{guru.nama_lengkap}</div>
                    <div class="text-secondary small">
                        NIP {guru.nip ?? '-'}
                    </div>
                    <div class="text-secondary small">
                        {guru.jenis_kelamin === 'L'
                            ? 'Laki-laki'
                            : guru.jenis_kelamin === 'P'
                              ? 'Perempuan'
                              : '-'}
                    </div>
                    {#if guru.is_aktif}
                        <Badge color="success" class="mt-1">Aktif</Badge>
                    {:else}
                        <Badge color="danger" class="mt-1">Nonaktif</Badge>
                    {/if}
                </div>
            </div>
        </div>
    {/if}

    {#if user}
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill"></i>
            <span class="small">
                Akun ini sudah terhubung ke pengguna ini:
                <b>{user.email}</b>
            </span>
        </div>
    {:else}
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span class="small"
                >Belum ada akun pengguna untuk guru ini. Buat akun di bawah ini.</span
            >
        </div>
    {/if}

    <div class="card border rounded-1 shadow-sm mb-3">
        <div class="card-body">
            <FormGroup>
                <Label for="nama_lengkap" class="text-xs">Nama Lengkap</Label>
                <Input
                    id="nama_lengkap"
                    type="text"
                    placeholder="Nama lengkap pengguna"
                    bind:value={form.name}
                    disabled={true}
                />
                {#if form.errors.name}
                    <small class="text-danger d-block mt-1"
                        >{form.errors.name}</small
                    >
                {/if}
            </FormGroup>

            <FormGroup>
                <Label for="email" class="text-xs">Email</Label>
                <Input
                    id="email"
                    type="email"
                    placeholder="email@example.com"
                    bind:value={form.email}
                    disabled={form.processing}
                />
                {#if form.errors.email}
                    <small class="text-danger d-block mt-1"
                        >{form.errors.email}</small
                    >
                {/if}
            </FormGroup>

            <FormGroup>
                <Label for="password" class="text-xs">
                    {isCreating
                        ? 'Password Baru'
                        : 'Password (kosongkan jika tidak diubah)'}
                </Label>
                <Input
                    autocomplete="off"
                    id="password"
                    type="password"
                    placeholder={isCreating
                        ? 'Minimal 8 karakter'
                        : 'Kosongkan jika tidak ingin mengganti'}
                    bind:value={form.password}
                    disabled={form.processing}
                />
                {#if form.errors.password}
                    <small class="text-danger d-block mt-1"
                        >{form.errors.password}</small
                    >
                {/if}
            </FormGroup>

            <FormGroup>
                <Label for="password_confirmation" class="text-xs"
                    >Konfirmasi Password</Label
                >
                <Input
                    autocomplete="off"
                    id="password_confirmation"
                    type="password"
                    placeholder="Ketik ulang password"
                    bind:value={form.password_confirmation}
                    disabled={form.processing}
                />
            </FormGroup>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <Button
            color="secondary"
            outline
            size="sm"
            onclick={() => router.visit(PengajarController.index().url)}
        >
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Pengajar
        </Button>
    </div>
</div>
