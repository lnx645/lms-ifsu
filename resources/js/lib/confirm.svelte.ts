export type ConfirmOptions = {
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    color?: string;
    icon?: string;
};

let open = $state(false);
let options = $state<ConfirmOptions>({});
let resolveFn: ((value: boolean) => void) | null = null;

export const confirm = {
    show(opts: ConfirmOptions = {}): Promise<boolean> {
        options = opts;
        open = true;

        return new Promise<boolean>((resolve) => {
            resolveFn = resolve;
        });
    },
    _accept() {
        open = false;
        resolveFn?.(true);
        resolveFn = null;
    },
    _cancel() {
        open = false;
        resolveFn?.(false);
        resolveFn = null;
    },
    get isOpen() {
        return open;
    },
    get current() {
        return options;
    },
};
