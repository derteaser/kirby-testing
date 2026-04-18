<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Matchers;

use Derteaser\KirbyTesting\Dom\Support\Normalize;

/**
 * @internal
 */
final class Classes implements Matcher
{
    public static function compare(mixed $expected, mixed $actual): bool
    {
        return !array_diff(Normalize::class((string) $expected), Normalize::class((string) $actual));
    }
}
