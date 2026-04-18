<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Support;

use DOMDocument;

/**
 * Pretty-prints the HTML snapshot shown by BaseAssert::dump(). Output is for
 * human eyeballs only, not downstream parsing.
 *
 * @internal
 */
final class HtmlFormatter
{
    public function format(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $dom = $this->convertToDomDocument($this->cleanupHtmlString($html));

        return $this->formatHtml((string) $dom->saveXML($dom->documentElement, LIBXML_NOEMPTYTAG));
    }

    private function convertToDomDocument(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_COMPACT | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NOXMLDECL);
        $dom->formatOutput = true;

        if ($dom->firstChild?->firstChild instanceof \DOMNode) {
            $dom->replaceChild($dom->firstChild->firstChild, $dom->firstChild);
        }

        return $dom;
    }

    private function formatHtml(string $html): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $html = $this->normalizeNewlines($html, "\r\n");
            $html = str_replace('&#13;', '', $html);
        }

        $html = $this->closeVoidElements($html);
        $html = $this->removeBody($html);

        return (string) preg_replace('/^[ ]+(?=<)/m', '$0$0', $html);
    }

    private function closeVoidElements(string $html): string
    {
        return (string) preg_replace(
            '~></(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)>~',
            ' />',
            $html,
        );
    }

    private function normalizeNewlines(string $html, string $replacement): string
    {
        return (string) preg_replace('~\R~u', $replacement, $html);
    }

    private function cleanupHtmlString(string $html): string
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $html = $this->normalizeNewlines((string) $html, "\n");

        return (string) preg_replace('~>[[:space:]]++<~m', '><', $html);
    }

    private function removeBody(string $html): string
    {
        $linebreak = PHP_EOL;
        $html = str_replace(["<body>{$linebreak}", "{$linebreak}</body>"], ['', ''], $html);

        return (string) preg_replace('/^[ ]{2}/m', '', $html);
    }
}
