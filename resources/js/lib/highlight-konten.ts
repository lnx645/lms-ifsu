import hljs from 'highlight.js/lib/core';
import xml from 'highlight.js/lib/languages/xml';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import typescript from 'highlight.js/lib/languages/typescript';
import php from 'highlight.js/lib/languages/php';
import plaintext from 'highlight.js/lib/languages/plaintext';

let registered = false;

function registerLanguages(): void {
    if (registered) {
        return;
    }

    hljs.registerLanguage('markup', xml);
    hljs.registerLanguage('xml', xml);
    hljs.registerLanguage('css', css);
    hljs.registerLanguage('javascript', javascript);
    hljs.registerLanguage('typescript', typescript);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('plaintext', plaintext);

    registered = true;
}

export function highlightKonten(host: HTMLElement): void {
    registerLanguages();

    host.querySelectorAll('pre code[class*="language-"]').forEach((el) => {
        const element = el as HTMLElement;
        if (element.dataset.highlighted) {
            return;
        }

        hljs.highlightElement(element);
        element.dataset.highlighted = 'yes';
    });
}