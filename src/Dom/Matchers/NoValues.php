<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Matchers;

/**
 * Boolean-only HTML attribute matcher (required, readonly, disabled, …).
 * Presence is the whole signal — attribute value is ignored.
 *
 * @internal
 */
final class NoValues implements Matcher
{
    public static function compare(mixed $expected, mixed $actual): bool
    {
        return true;
    }
}
