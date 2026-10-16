<script lang="ts">
    import AuthenticatedSessionController from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
    import SocialiteController from '@/actions/App/Http/Controllers/SocialiteController';
    import { useForm } from '@inertiajs/svelte';
    import {
        Button,
        Card,
        CardBody,
        Col,
        FormGroup,
        Input,
        InputGroup,
        InputGroupText,
        Label,
        Row,
    } from '@sveltestrap/sveltestrap';
    import '@/styles/modules/login.scss';
    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    let passwordVisible = $state(false);
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });
    function submit(e: any) {
        e.preventDefault();
        form.submit(AuthenticatedSessionController.store());
    }
</script>

<div
    class={`login-page min-vh-100 d-flex align-items-center login-page`}
>
    <Card
        class={`login-card w-100 m-auto login-card`}
        style="max-width: 940px; border: none;"
    >
        <Row class="g-0">
            <Col
                lg={5}
                class={`login-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white login-brand`}
            >
                <div class="d-flex align-items-center gap-3">
                    <img
                        class="login-logo"
                        src="https://smkifsu.sch.id/assets/img/logo.png"
                        alt="Lambang Tut Wuri Handayani"
                    />
                    <div>
                        <div
                            class="d-flex align-items-center gap-2 mb-1"
                        >
                            <span class="lh-1 fw-bold fs-5"
                                >{appName}</span
                            >
                        </div>
                        <div
                            class="brand-title fs-6 fw-normal opacity-75"
                        >
                            LMSSmk Informatika Sumedang
                        </div>
                    </div>
                </div>

                <div
                    class="d-flex flex-column align-items-center text-center my-4"
                >
                    <img
                        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS8lyCvH94bzd4Zo92Pep1aym-_ipv98jkPYoAFv0rzsg&s=10"
                        alt="Foto Ki Hajar Dewantara"
                        class="img-fluid user-select-none rounded-circle mb-4 border border-4 border-warning shadow"
                        style="width: 140px; height: 140px; object-fit: cover;pointer-events:none;"
                    />
                    <p class={`quote-text mb-0 fw-medium quote-text`}>
                        "Ing Ngarso Sung Tulodo,<br />Ing Madya Mangun
                        Karso,<br />Tut Wuri Handayani"
                    </p>
                </div>

                <div class="text-center small opacity-75"></div>
            </Col>

            <Col lg={7} style="background-color: var(--app-shell-surface);">
                <CardBody class="p-4 p-sm-5">
                    <div class="d-lg-none mb-4">
                        <h2 class="h4 mb-0">{appName}</h2>
                        <span class={`text-uppercase brand-kicker`}
                            >Ujian Berbasis Komputer</span
                        >
                    </div>

                    <h2 class="h4 mb-1 fw-bold">Masuk</h2>
                    <p class="text-muted mb-4 small">
                        Silakan masuk untuk melanjutkan ke halaman
                        ujian.
                    </p>

                    <form onsubmit={submit}>
                        <FormGroup class="mb-3">
                            <Label
                                for="username"
                                class="text-dark small fw-semibold"
                                >NISN / Email</Label
                            >
                            <InputGroup>
                                <InputGroupText>
                                    <i
                                        class={`bi bi-person login-icon text-muted login-icon`}
                                    ></i>
                                </InputGroupText>
                                <Input
                                    bind:value={form.email}
                                    invalid={!!form.errors.email}
                                    id="username"
                                    placeholder="Masukkan NISN atau email"
                                />
                            </InputGroup>
                        </FormGroup>

                        <FormGroup class="mb-3">
                            <Label
                                for="password"
                                class="text-dark small fw-semibold"
                                >Kata Sandi</Label
                            >
                            <InputGroup>
                                <InputGroupText>
                                    <i
                                        class={`bi bi-lock login-icon text-muted login-icon`}
                                    ></i>
                                </InputGroupText>
                                <Input
                                    bind:value={form.password}
                                    invalid={!!form.errors.password}
                                    id="password"
                                    type={passwordVisible
                                        ? 'text'
                                        : 'password'}
                                    placeholder="Masukkan kata sandi"
                                />
                                <button
                                    type="button"
                                    class="btn border bg-transparent d-flex align-items-center pe-3"
                                    style="color: var(--inv-gray-600); border-color: var(--inv-gray-100);"
                                    onclick={() =>
                                        (passwordVisible =
                                            !passwordVisible)}
                                    aria-label={passwordVisible
                                        ? 'Sembunyikan kata sandi'
                                        : 'Tampilkan kata sandi'}
                                >
                                    {#if passwordVisible}
                                        <i
                                            class={`bi bi-eye-slash login-icon login-icon`}
                                        ></i>
                                    {:else}
                                        <i
                                            class={`bi bi-eye login-icon login-icon`}
                                        ></i>
                                    {/if}
                                </button>
                            </InputGroup>
                        </FormGroup>

                        <Row class="align-items-center mb-4 mt-2">
                            <Col xs={7}>
                                <div
                                    class={`remember-check ms-1 form-check form-switch mb-0 remember-check`}
                                >
                                    <input
                                        id="remember"
                                        type="checkbox"
                                        class="form-check-input"
                                        bind:checked={form.remember}
                                    />
                                    <label
                                        for="remember"
                                        class="form-check-label text-muted small user-select-none"
                                    >
                                        Ingat saya
                                    </label>
                                </div>
                            </Col>
                            <Col xs={5} class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-link p-0 small fw-semibold text-decoration-none"
                                    style="color: var(--inv-primary-500)"
                                    >Lupa kata sandi?</button
                                >
                            </Col>
                        </Row>
                        <Button
                            color="primary"
                            size="lg"
                            class="w-100 d-flex align-items-center justify-content-center gap-2"
                            onclick={submit}
                        >
                            <span class="fs-6">Masuk</span>
                            <i
                                class={`bi bi-arrow-right login-icon login-icon`}
                            ></i>
                        </Button>
                    </form>

                    <div class="d-flex align-items-center gap-3 my-4">
                        <span class="divider-line"></span>
                        <span class="text-muted small text-nowrap"
                            >atau</span
                        >
                        <span class="divider-line"></span>
                    </div>

                    <a
                        href={SocialiteController.redirect().url}
                        class={`google-btn btn w-100 d-flex align-items-center justify-content-center gap-2 py-2 google-btn`}
                    >
                        <i
                            class={`bi bi-google google-icon google-icon`}
                            style="color: #EA4335;"
                        ></i>
                        <span class="small fw-semibold"
                            >Masuk dengan Google</span
                        >
                    </a>

                    <p class="text-center text-muted small mt-4 mb-0">
                        Butuh bantuan? Hubungi operator sekolah Anda.
                    </p>
                </CardBody>
            </Col>
        </Row>
    </Card>
</div>
