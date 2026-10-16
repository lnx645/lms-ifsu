import { Heading } from '@tiptap/extension-heading';
import { mergeAttributes, Node } from '@tiptap/core';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        daftarIsi: {
            insertDaftarIsi: () => ReturnType;
        };
        audioPlayer: {
            setAudio: (src: string) => ReturnType;
        };
    }
}

export type DaftarIsiItem = {
    id: string;
    text: string;
    level: number;
};

const slugify = (text: string, index: number): string => `judul-${index + 1}`;

/**
 * Heading yang menyimpan id agar bisa dijadikan target tautan daftar isi.
 */
export const HeadingWithId = Heading.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            id: {
                default: null,
                parseHTML: (element) => element.getAttribute('id'),
                renderHTML: (attributes) => (attributes.id ? { id: attributes.id } : {}),
            },
        };
    },
});

/**
 * Node blok untuk daftar isi yang tersusun dari heading di dokumen.
 * Atribut `items` diperbarui otomatis lewat `syncDaftarIsi`.
 */
export const DaftarIsi = Node.create({
    name: 'daftarIsi',
    group: 'block',
    atom: true,

    addAttributes() {
        return {
            items: {
                default: [] as DaftarIsiItem[],
                parseHTML: (element) => {
                    const links = [...element.querySelectorAll('a[href^="#judul-"]')].map((a) => ({
                        id: a.getAttribute('href')?.slice(1) ?? '',
                        text: a.textContent ?? '',
                        level: Number.parseInt(a.closest('li')?.getAttribute('data-level') ?? '2', 10) || 2,
                    }));

                    return links;
                },
                renderHTML: () => ({}),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-daftar-isi]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        const items: DaftarIsiItem[] = (node.attrs.items as DaftarIsiItem[]) ?? [];
        const listItems = items.map((item) => [
            'li',
            { class: 'daftar-isi__item', 'data-level': String(item.level) },
            ['a', { href: `#${item.id}`, class: 'daftar-isi__link' }, item.text],
        ]);

        return [
            'div',
            mergeAttributes(HTMLAttributes, { 'data-daftar-isi': 'true' }),
            ['div', { class: 'daftar-isi__title' }, 'Daftar Isi'],
            ['ol', { class: 'daftar-isi__list' }, ...listItems],
        ];
    },

    addCommands() {
        return {
            insertDaftarIsi:
                () =>
                ({ commands }) =>
                    commands.insertContent({ type: this.name }),
        };
    },
});

/**
 * Node blok untuk pemutar audio (mp3/ogg/wav lewat URL).
 */
export const AudioPlayer = Node.create({
    name: 'audioPlayer',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            src: {
                default: null,
                parseHTML: (element) => element.querySelector('audio')?.getAttribute('src') ?? null,
                renderHTML: (attributes) => (attributes.src ? { src: attributes.src } : {}),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-audio-player]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return [
            'div',
            { 'data-audio-player': 'true' },
            ['audio', mergeAttributes(HTMLAttributes, { controls: 'true', preload: 'metadata', class: 'audio-player' })],
        ];
    },

    addCommands() {
        return {
            setAudio:
                (src: string) =>
                ({ commands }) =>
                    commands.insertContent({ type: this.name, attrs: { src } }),
        };
    },
});

/**
 * Kumpulkan heading dari dokumen Tiptap (JSON) beserta urutannya.
 */
const collectHeadings = (json: Record<string, any>): { text: string; level: number }[] => {
    const headings: { text: string; level: number }[] = [];

    const walk = (node: any): void => {
        if (node.type === 'heading' && typeof node.attrs?.level === 'number') {
            const text = (node.content ?? [])
                .filter((child: any) => child.type === 'text')
                .map((child: any) => child.text ?? '')
                .join('')
                .trim();

            if (text) {
                headings.push({ text, level: node.attrs.level });
            }
        }

        for (const child of node.content ?? []) {
            walk(child);
        }
    };

    walk(json);

    return headings;
};

/**
 * Sinkronkan id heading dan isi daftar isi setelah dokumen berubah.
 * Dipanggil dari editor (mis. onUpdate / onTransaction).
 */
export const syncDaftarIsi = (editor: any): void => {
    if (!editor?.state?.doc) {
        return;
    }

    const headings = collectHeadings(editor.getJSON());

    const tr = editor.state.tr;
    let changed = false;
    let headingIndex = 0;

    editor.state.doc.descendants((node: any, pos: number): void => {
        if (node.type.name === 'heading') {
            const id = slugify(
                (node.content?.textBetween ?? node.textContent) || 'judul',
                headingIndex,
            );
            headingIndex += 1;

            if (node.attrs.id !== id) {
                tr.setNodeMarkup(pos, undefined, { ...node.attrs, id });
                changed = true;
            }
        }

        if (node.type.name === 'daftarIsi') {
            const items = headings.map((heading, index) => ({
                id: slugify(heading.text, index),
                text: heading.text,
                level: heading.level,
            }));

            if (JSON.stringify(node.attrs.items) !== JSON.stringify(items)) {
                tr.setNodeMarkup(pos, undefined, { ...node.attrs, items });
                changed = true;
            }
        }
    });

    if (changed) {
        editor.view.dispatch(tr);
    }
};
