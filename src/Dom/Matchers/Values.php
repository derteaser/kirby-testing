<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Matchers;

/**
 * @internal
 */
final class Values implements Matcher
{
    public static function compare(mixed $expected, mixed $actual): bool
    {
        return $expected === $actual;
    }
}
