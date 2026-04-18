<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Support;

use Derteaser\KirbyTesting\Dom\Matchers\Classes;
use Derteaser\KirbyTesting\Dom\Matchers\NoValues;
use Derteaser\KirbyTesting\Dom\Matchers\Text;
use Derteaser\KirbyTesting\Dom\Matchers\Values;

/**
 * @internal
 */
final class CompareAttributes
{
    public static function compare(string $attribute, mixed $value, mixed $actual): bool
    {
        if (!$value) {
            return NoValues::compare($value, $actual);
        }

        return match ($attribute) {
            'class' => Classes::compare($value, $actual),
            'required', 'readonly' => NoValues::compare($value, $actual),
            'text', 'value' => Text::compare($value, $actual),
            default => Values::compare($value, $actual),
        };
    }
}
