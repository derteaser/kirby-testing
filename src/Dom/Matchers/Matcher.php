<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Matchers;

interface Matcher
{
    public static function compare(mixed $expected, mixed $actual): bool;
}
