<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Support;

/**
 * @internal
 */
final class Normalize
{
    /**
     * Split a class attribute on whitespace and sort its tokens so classes
     * compare as an unordered set. Empty tokens are dropped.
     *
     * @return list<string>
     */
    public static function class(string $class): array
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', trim($class)) ?: [], static fn(string $t): bool => $t !== ''));
        sort($tokens);

        return $tokens;
    }

    /**
     * Collapse vertical whitespace and runs of whitespace so text compares
     * equal regardless of inherited HTML formatting.
     */
    public static function text(string $text): string
    {
        return (string) preg_replace(['/\v+/', '/\s+/'], ['', ' '], trim($text));
    }
}
