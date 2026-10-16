// Grammar highlight.js yang didukung: HTML/XML, CSS, JavaScript, TypeScript, PHP (+ plaintext).
// php butuh xml, typescript butuh javascript. Bahasa "markup" ada di modul xml.js.

import { createLowlight } from 'lowlight';
import type { LanguageFn } from 'highlight.js';
import gxml from 'highlight.js/lib/languages/xml';
import gcss from 'highlight.js/lib/languages/css';
import gjavascript from 'highlight.js/lib/languages/javascript';
import gtypescript from 'highlight.js/lib/languages/typescript';
import gphp from 'highlight.js/lib/languages/php';
import gplaintext from 'highlight.js/lib/languages/plaintext';

const grammars: Record<string, LanguageFn> = {
    markup: gxml,
    xml: gxml,
    css: gcss,
    javascript: gjavascript,
    typescript: gtypescript,
    php: gphp,
    plaintext: gplaintext,
};

export const lowlight = createLowlight(grammars);