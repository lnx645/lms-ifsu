<script lang="ts">
    import StarterKit from '@tiptap/starter-kit';
    import { Editor } from '@tiptap/core';
    import Link from '@tiptap/extension-link';
    import Mathematics from '@tiptap/extension-mathematics';
    import Image from '@tiptap/extension-image';
    import { onDestroy, onMount } from 'svelte';
    import {
        HeadingWithId,
        syncDaftarIsi,
    } from '@/lib/tiptap/custom-extensions';
    import 'katex/dist/katex.min.css';

    const CODE_LANGUAGES: { value: string; label: string }[] = [
        { value: 'markup', label: 'HTML/XML' },
        { value: 'css', label: 'CSS' },
        { value: 'javascript', label: 'JavaScript' },
        { value: 'typescript', label: 'TypeScript' },
        { value: 'php', label: 'PHP' },
        { value: 'plaintext', label: 'Plain Text' },
    ];

    let {
        id,
        value = '',
        placeholder = '',
        invalid = false,
        onchange,
    }: {
        id?: string;
        value?: string;
        placeholder?: string;
        invalid?: boolean;
        onchange?: (html: string) => void;
    } = $props();

    let element: HTMLDivElement | undefined = $state();
    let editor: Editor | undefined = $state();
    let transactionVersion = $state(0);

    onMount(() => {
        editor = new Editor({
            element,
            extensions: [
                HeadingWithId,
                StarterKit.configure({
                    heading: false,
                    codeBlock: false,
                }),
                Link.configure({
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer nofollow',
                        target: '_blank',
                    },
                }),
                Image.configure({
                    HTMLAttributes: {
                        class: 'img-fluid rounded',
                    },
                }),
                Mathematics.configure({
                    inlineOptions: {
                        onClick: (node, pos) => {
                            const latex = window.prompt(
                                'Edit rumus (LaTeX):',
                                node.attrs.latex,
                            );
                            if (latex !== null) {
                                editor
                                    ?.chain()
                                    .setNodeSelection(pos)
                                    .updateInlineMath({ latex })
                                    .focus()
                                    .run();
                            }
                        },
                    },
                    blockOptions: {
                        onClick: (node, pos) => {
                            const latex = window.prompt(
                                'Edit rumus (LaTeX):',
                                node.attrs.latex,
                            );
                            if (latex !== null) {
                                editor
                                    ?.chain()
                                    .setNodeSelection(pos)
                                    .updateBlockMath({ latex })
                                    .focus()
                                    .run();
                            }
                        },
                    },
                    katexOptions: { throwOnError: false },
                }),
            ],
            content: value,
            editorProps: {
                attributes: {
                    class: 'rich-editor__content',
                    ...(placeholder
                        ? { 'data-placeholder': placeholder }
                        : {}),
                },
            },
            onUpdate: ({ editor: current }) => {
                onchange?.(current.getHTML());
            },
            onTransaction: ({ transaction, editor: current }) => {
                void transaction;
                transactionVersion++;
                syncDaftarIsi(current);
            },
        });

        return () => {
            editor?.destroy();
            editor = undefined;
        };
    });

    onDestroy(() => {
        editor?.destroy();
    });

    type ToolbarCommand =
        | 'bold'
        | 'italic'
        | 'underline'
        | 'subscript'
        | 'superscript'
        | 'bulletList'
        | 'orderedList'
        | 'math'
        | 'blockMath'
        | 'image';

    function toggle(command: ToolbarCommand) {
        if (!editor) {
            return;
        }

        const chain = editor.chain().focus();

        switch (command) {
            case 'bold':
                chain.toggleBold();
                break;
            case 'italic':
                chain.toggleItalic();
                break;
            case 'underline':
                chain.toggleUnderline();
                break;
            case 'subscript':
                // @ts-ignore - subscript dari StarterKit
                chain.toggleSubscript();
                break;
            case 'superscript':
                // @ts-ignore - superscript dari StarterKit
                chain.toggleSuperscript();
                break;
            case 'bulletList':
                chain.toggleBulletList();
                break;
            case 'orderedList':
                chain.toggleOrderedList();
                break;
            case 'math':
                {
                    const latex = window.prompt(
                        'Rumus matematika (LaTeX):',
                        '\\frac{a}{b}',
                    );
                    if (latex !== null && latex.trim() !== '') {
                        chain.insertInlineMath({
                            latex: latex.trim(),
                        });
                    }
                }
                break;
            case 'blockMath':
                {
                    const latex = window.prompt(
                        'Rumus matematika (LaTeX):',
                        'E = mc^2',
                    );
                    if (latex !== null && latex.trim() !== '') {
                        chain.insertBlockMath({
                            latex: latex.trim(),
                        });
                    }
                }
                break;
            case 'image':
                {
                    const url = window.prompt(
                        'URL gambar:',
                        'https://',
                    );
                    if (url !== null && url.trim() !== '') {
                        chain.setImage({ src: url.trim() });
                    }
                }
                break;
        }

        chain.run();
    }

    const activeStates = $derived.by(() => {
        void transactionVersion;

        return {
            bold: editor?.isActive('bold') ?? false,
            italic: editor?.isActive('italic') ?? false,
            underline: editor?.isActive('underline') ?? false,
            bulletList: editor?.isActive('bulletList') ?? false,
            orderedList: editor?.isActive('orderedList') ?? false,
            heading1:
                editor?.isActive('heading', { level: 1 }) ?? false,
            heading2:
                editor?.isActive('heading', { level: 2 }) ?? false,
            heading3:
                editor?.isActive('heading', { level: 3 }) ?? false,
        };
    });

    const tools: {
        command: ToolbarCommand;
        icon: string;
        label: string;
    }[] = [
        { command: 'bold', icon: 'bi-type-bold', label: 'Tebal' },
        { command: 'italic', icon: 'bi-type-italic', label: 'Miring' },
        { command: 'underline', icon: 'bi-type-underline', label: 'Garis bawah' },
        { command: 'subscript', icon: 'bi-subscript', label: 'Subskrip (H₂O)' },
        { command: 'superscript', icon: 'bi-superscript', label: 'Superskrip (x²)' },
        { command: 'bulletList', icon: 'bi-list-ul', label: 'Daftar berpoin' },
        { command: 'orderedList', icon: 'bi-list-ol', label: 'Daftar bernomor' },
        { command: 'math', icon: 'bi-123', label: 'Rumus matematika' },
        { command: 'blockMath', icon: 'bi-fullscreen', label: 'Rumus blok' },
        { command: 'image', icon: 'bi-image', label: 'Gambar (URL)' },
    ];
</script>

<div class={`rich-editor ${invalid ? 'is-invalid' : ''}`} {id}>
    <div class="rich-editor__toolbar">
        {#each tools as tool (tool.command)}
            <button
                type="button"
                class="rich-editor__tool {activeStates[tool.command]
                    ? 'is-active'
                    : ''}"
                onclick={() => toggle(tool.command)}
                title={tool.label}
                aria-label={tool.label}
                aria-pressed={activeStates[tool.command]}
            >
                <i class={`bi ${tool.icon}`}></i>
            </button>
        {/each}
    </div>
    <div class="rich-editor__frame" bind:this={element}></div>
</div>

<style>
    .rich-editor {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        overflow: hidden;
        background: var(--bs-body-bg);
    }

    .rich-editor.is-invalid {
        border-color: var(--bs-form-invalid-border-color);
    }

    .rich-editor.is-invalid:focus-within {
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-danger-rgb), 0.25);
    }

    .rich-editor__toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.375rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }

    .rich-editor__tool {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        padding: 0;
        border: 1px solid transparent;
        border-radius: var(--bs-border-radius-sm);
        background: transparent;
        color: var(--bs-body-color);
        cursor: pointer;
    }

    .rich-editor__tool:hover {
        background: var(--bs-secondary-bg);
    }

    .rich-editor__tool.is-active {
        background: var(--bs-primary);
        color: #fff;
    }

    .rich-editor__lang {
        width: 8rem;
        margin-left: auto;
        font-size: var(--bs-font-size-sm);
        padding: 0.2rem 0.4rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius-sm);
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
    }

    .rich-editor__lang:focus {
        border-color: var(--bs-primary);
        outline: none;
    }

    :global(.rich-editor__content) {
        min-height: 120px;
        padding: 0.625rem 0.75rem;
        outline: none;
    }

    :global(.rich-editor__content p) {
        margin-bottom: 0.5rem;
    }

    :global(.rich-editor__content p:last-child) {
        margin-bottom: 0;
    }

    :global(.rich-editor__content.is-empty::before) {
        content: attr(data-placeholder);
        color: var(--bs-secondary-color);
        pointer-events: none;
        float: left;
        height: 0;
    }

    :global(.rich-editor__content h1) {
        font-size: 1.5rem;
        margin: 1rem 0 0.5rem;
    }

    :global(.rich-editor__content h2) {
        font-size: 1.25rem;
        margin: 0.9rem 0 0.45rem;
    }

    :global(.rich-editor__content h3) {
        font-size: 1.1rem;
        margin: 0.8rem 0 0.4rem;
    }

    :global(.rich-editor__content a) {
        color: var(--bs-primary);
    }

    :global(.rich-editor__content .youtube-embed) {
        display: flex;
        justify-content: center;
        margin: 0.75rem 0;
    }

    :global(.rich-editor__content iframe) {
        max-width: 100%;
        border: 0;
        border-radius: var(--bs-border-radius);
    }

    :global(.rich-editor__content .audio-player) {
        width: 100%;
        margin: 0.75rem 0;
    }

    :global(.rich-editor__content .daftar-isi) {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        background: var(--bs-tertiary-bg);
        padding: 0.75rem 1rem;
        margin: 0.75rem 0;
    }

    :global(.rich-editor__content .daftar-isi__title) {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    :global(.rich-editor__content .daftar-isi__list) {
        margin: 0;
        padding-left: 1.25rem;
    }

    :global(.rich-editor__content .daftar-isi__item) {
        margin-bottom: 0.2rem;
    }

    :global(.rich-editor__content .daftar-isi__item[data-level='1']) {
        margin-top: 0.3rem;
    }

    :global(.rich-editor__content .daftar-isi__item[data-level='3']) {
        padding-left: 1rem;
    }

    :global(.rich-editor__content .daftar-isi__link) {
        text-decoration: none;
    }

    :global(.rich-editor__content .tiptap-mathematics-render) {
        cursor: pointer;
    }

    :global(.rich-editor__content pre) {
        position: relative;
        background: var(--bs-body-bg, #f6f8fa);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        padding: 0.75rem 1rem;
        margin: 0.75rem 0;
        overflow-x: auto;
    }

    :global(.rich-editor__content pre code) {
        background: none;
        padding: 0;
        font-family: var(--bs-font-monospace);
        font-size: 0.875rem;
        line-height: 1.6;
    }

    :global(.rich-editor__content pre::before) {
        content: attr(data-language);
        display: block;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bs-secondary-color);
        margin-bottom: 0.5rem;
    }

    :global(.rich-editor__content code) {
        background: var(--bs-secondary-bg);
        border-radius: 0.25rem;
        padding: 0.1em 0.35em;
        font-size: 0.875em;
        font-family: var(--bs-font-monospace);
    }
</style>
