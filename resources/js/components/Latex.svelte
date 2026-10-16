<script lang="ts">
    import katex from 'katex';
    import 'katex/dist/katex.min.css';

    let { teks = '', block = false }: { teks: string | null | undefined; block?: boolean } = $props();

    // Render teks yang mungkin mengandung LaTeX
    // Mendukung: $...$ (inline), $$...$$ (block), \(...\), \[...\]
    function renderLatex(input: string | null | undefined): string {
        if (!input) return '';
        let out = input;

        // Tangani TipTap Mathematics: render ulang dari data-latex kalau div kosong
        // Format: <div data-latex="..." data-type="block-math"></div>
        //      atau <span data-latex="..." data-type="inline-math"></span>
        out = out.replace(/<(div|span)([^>]*?)data-latex="([^"]*)"([^>]*?)data-type="(block-math|inline-math)"([^>]*?)>(.*?)<\/\1>/gs,
            (match, tag, pre1, latex, pre2, mathType, pre3, inner) => {
                const displayMode = mathType === 'block-math';
                // decode HTML entities di latex
                const decoded = latex
                    .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
                    .replace(/&amp;/g, '&').replace(/&quot;/g, '"')
                    .replace(/&#039;/g, "'");
                try {
                    const rendered = katex.renderToString(decoded.trim(), { displayMode, throwOnError: false });
                    return `<${tag}${pre1}data-latex="${latex}"${pre2}data-type="${mathType}"${pre3}>${rendered}</${tag}>`;
                } catch {
                    return match;
                }
            });
        // Juga tangani kalau atribut terbalik (data-type dulu baru data-latex)
        out = out.replace(/<(div|span)([^>]*?)data-type="(block-math|inline-math)"([^>]*?)data-latex="([^"]*)"([^>]*?)>(.*?)<\/\1>/gs,
            (match, tag, pre1, mathType, pre2, latex, pre3, inner) => {
                const displayMode = mathType === 'block-math';
                const decoded = latex
                    .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
                    .replace(/&amp;/g, '&').replace(/&quot;/g, '"')
                    .replace(/&#039;/g, "'");
                try {
                    const rendered = katex.renderToString(decoded.trim(), { displayMode, throwOnError: false });
                    return `<${tag}${pre1}data-type="${mathType}"${pre2}data-latex="${latex}"${pre3}>${rendered}</${tag}>`;
                } catch {
                    return match;
                }
            });

        // Block: $$...$$ atau \[...\]
        out = out.replace(/\$\$([\s\S]+?)\$\$/g, (_, formula) => {
            try {
                return katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false });
            } catch {
                return _;
            }
        });
        out = out.replace(/\\\[([\s\S]+?)\\\]/g, (_, formula) => {
            try {
                return katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false });
            } catch {
                return _;
            }
        });

        // Inline: $...$ atau \(...\)
        out = out.replace(/\$([^$\n]+?)\$/g, (_, formula) => {
            try {
                return katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false });
            } catch {
                return _;
            }
        });
        out = out.replace(/\\\((.+?)\\\)/g, (_, formula) => {
            try {
                return katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false });
            } catch {
                return _;
            }
        });

        return out;
    }

    const html = $derived(renderLatex(teks ?? ''));
</script>

{#if block}
    <div class="latex-block">{@html html}</div>
{:else}
    <span class="latex-inline">{@html html}</span>
{/if}

<style>
    .latex-block :global(.katex-display) {
        margin: 0.5em 0;
    }
</style>
