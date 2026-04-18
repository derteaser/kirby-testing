<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Matchers;

use Derteaser\KirbyTesting\Dom\Support\Normalize;

/**
 * @internal
 */
final class Text implements Matcher
{
    public static function compare(mixed $expected, mixed $actual): bool
    {
        return Normalize::text((string) $expected) === Normalize::text((string) $actual);
    }
}
