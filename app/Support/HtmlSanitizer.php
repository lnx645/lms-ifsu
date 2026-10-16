<?php

namespace App\Support;

/**
 * Sanitasi HTML dari RichTextEditor untuk cegah XSS.
 *
 * Mengizinkan hanya tag formatting aman (p, br, strong, em, dst.)
 * dan menghapus <script>, event handler (onclick, dll), serta
 * atribut berbahaya seperti javascript: URLs.
 */
class HtmlSanitizer
{
    /**
     * Tag yang diizinkan beserta atribut yang diizinkan per tag.
     *
     * @var array<string, string[]>
     */
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'code' => [],
        'pre' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'span' => ['class', 'data-type', 'data-latex'],
        'div' => ['class', 'data-type', 'data-latex'],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => [],
        'td' => [],
        'sub' => [],
        'sup' => [],
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if (! $wrapper) {
            return '';
        }

        self::sanitizeNode($wrapper);

        $result = '';
        foreach ($wrapper->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        // Proses children dulu (iterasi mundur karena bisa dihapus).
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);

                if (! isset(self::ALLOWED[$tag])) {
                    // Tag tidak diizinkan: ganti dengan isi text-nya saja.
                    $text = $child->ownerDocument->createTextNode($child->textContent);
                    $node->replaceChild($text, $child);

                    continue;
                }

                // Hapus atribut yang tidak diizinkan + event handler.
                $allowedAttrs = self::ALLOWED[$tag];
                $toRemove = [];
                foreach ($child->attributes as $attr) {
                    $name = strtolower($attr->name);
                    if (str_starts_with($name, 'on') || ! in_array($name, $allowedAttrs, true)) {
                        $toRemove[] = $attr->name;

                        continue;
                    }
                    // Tolak javascript: dan data: URLs.
                    if (in_array($name, ['href', 'src'], true)) {
                        $val = trim(strtolower($attr->value));
                        if (str_starts_with($val, 'javascript:') || str_starts_with($val, 'data:text/html')) {
                            $toRemove[] = $attr->name;
                        }
                    }
                }
                foreach ($toRemove as $name) {
                    $child->removeAttribute($name);
                }

                // Paksa rel="noopener" untuk link target=_blank.
                if ($tag === 'a' && strtolower($child->getAttribute('target')) === '_blank') {
                    $child->setAttribute('rel', 'noopener');
                }

                self::sanitizeNode($child);
            } elseif ($child instanceof \DOMComment) {
                $node->removeChild($child);
            }
        }
    }
}
