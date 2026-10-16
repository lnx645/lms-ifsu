import 'vitest-dom/extend-expect';
import { vi } from 'vitest';

type RouterGetOptions = {
    preserveState?: boolean;
    preserveScroll?: boolean;
    replace?: boolean;
    only?: string[];
    onSuccess?: () => void;
    onFinish?: () => void;
};

type FormCall = {
    method: string;
    url: string;
    payload: Record<string, unknown>;
    options?: Record<string, unknown>;
};

const FORM_KEYS = new Set([
    'data',
    'errors',
    'processing',
    'wasSuccessful',
    'recentlySuccessful',
    'isDirty',
    'calls',
    'transformCb',
    'clearErrors',
    'reset',
    'setError',
    'setDefaults',
    'transform',
    'get',
    'post',
    'put',
    'patch',
    'delete',
]);

const { router, usePage, useForm, inertia, WhenVisible } = vi.hoisted(
    () => {
        const state = {
            url: '/app/guru/materi',
            props: {} as Record<string, unknown>,
        };

        function makeForm(initial: Record<string, unknown>) {
            const form: Record<string, unknown> & {
                data: Record<string, unknown>;
                errors: Record<string, string>;
                processing: boolean;
                calls: FormCall[];
                clearErrors: ReturnType<typeof vi.fn>;
                reset: ReturnType<typeof vi.fn>;
                setError: ReturnType<typeof vi.fn>;
                setDefaults: ReturnType<typeof vi.fn>;
                transform: ReturnType<typeof vi.fn>;
                get: ReturnType<typeof vi.fn>;
                post: ReturnType<typeof vi.fn>;
                put: ReturnType<typeof vi.fn>;
                patch: ReturnType<typeof vi.fn>;
                delete: ReturnType<typeof vi.fn>;
            } = {
                ...initial,
                data: { ...initial },
                errors: {},
                processing: false,
                wasSuccessful: false,
                recentlySuccessful: false,
                isDirty: false,
                calls: [],
                clearErrors: vi.fn(function (this: typeof form) {
                    this.errors = {};
                }),
                reset: vi.fn(function (this: typeof form) {
                    Object.keys(this).forEach((key) => {
                        if (key in initial) {
                            this[key] = initial[key];
                        }
                    });
                    this.errors = {};
                }),
                setError: vi.fn(function (
                    this: typeof form,
                    key: string,
                    value: string,
                ) {
                    this.errors[key] = value;
                }),
                setDefaults: vi.fn(),
                transform: vi.fn(function (
                    this: typeof form,
                    cb: (data: Record<string, unknown>) => unknown,
                ) {
                    this.transformCb = cb;
                }),
                get: vi.fn(submit('get')),
                post: vi.fn(submit('post')),
                put: vi.fn(submit('put')),
                patch: vi.fn(submit('patch')),
                delete: vi.fn(submit('delete')),
            };

            function submit(method: string) {
                return function (
                    this: typeof form,
                    url: string,
                    options?: Record<string, unknown>,
                ) {
                    const payload = Object.fromEntries(
                        Object.entries(this).filter(
                            ([key]) => !FORM_KEYS.has(key),
                        ),
                    );
                    this.calls.push({
                        method,
                        url,
                        payload,
                        options,
                    });
                    options?.onSuccess?.();
                    options?.onFinish?.();
                };
            }

            return form;
        }

        const router = {
            get: vi.fn(function (
                url: string,
                _data: unknown,
                options?: RouterGetOptions,
            ) {
                state.url = url;
                options?.onSuccess?.();
                options?.onFinish?.();

                return Promise.resolve();
            }),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            reload: vi.fn(),
            visit: vi.fn(),
            cancel: vi.fn(),
        };

        const usePage = vi.fn(() => ({
            url: state.url,
            props: state.props,
            component: null,
            version: null,
        }));

        const useForm = vi.fn((initial: Record<string, unknown>) =>
            makeForm(initial),
        );

        const inertia = vi.fn((_node: HTMLElement) => ({
            destroy: () => {},
        }));

        const WhenVisible = vi.fn(
            (_props: Record<string, unknown>) => ({
                render: () => ({
                    html: '',
                    head: '',
                    css: { code: '', map: null },
                }),
                reset: () => {},
                set: () => {},
                destroy: () => {},
            }),
        );

        return { router, usePage, useForm, inertia, WhenVisible };
    },
);

vi.mock('@inertiajs/svelte', () => ({
    router,
    usePage,
    useForm,
    inertia,
    WhenVisible,
}));

vi.mock(
    '@/components/RichTextEditor.svelte',
    () => import('./stubs/RichTextEditor.svelte'),
);

vi.mock(
    '@/components/DocxViewer.svelte',
    () => import('./stubs/ViewerStub.svelte'),
);
vi.mock(
    '@/components/PdfViewer.svelte',
    () => import('./stubs/ViewerStub.svelte'),
);
vi.mock(
    '@/components/PptxViewer.svelte',
    () => import('./stubs/ViewerStub.svelte'),
);
vi.mock(
    '@/components/XlsxViewer.svelte',
    () => import('./stubs/ViewerStub.svelte'),
);

vi.mock(
    '@/components/DatePicker/VanillaDatePicker.svelte',
    () => import('./stubs/VanillaDatePickerStub.svelte'),
);

vi.mock('@/lib/confirm.svelte', () => {
    const confirmMock = vi.fn(() => Promise.resolve(true)) as ReturnType<
        typeof vi.fn
    > & { show: () => Promise<boolean> };
    confirmMock.show = vi.fn(() => Promise.resolve(true));

    return { confirm: confirmMock };
});

beforeEach(() => {
    vi.clearAllMocks();
});
