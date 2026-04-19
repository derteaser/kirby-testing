<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Constraints;

use PHPUnit\Framework\Constraint\Constraint;

final class SeeInOrder extends Constraint
{
    protected string $content;

    protected string $failedValue = '';

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @param  array<int, string>  $other
     */
    public function matches($other): bool
    {
        $decodedContent = html_entity_decode($this->content, ENT_QUOTES, 'UTF-8');
        $position = 0;

        foreach ($other as $value) {
            if (empty($value)) {
                continue;
            }

            $decodedValue = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
            $valuePosition = mb_strpos($decodedContent, $decodedValue, $position);

            if ($valuePosition === false || $valuePosition < $position) {
                $this->failedValue = (string) $value;

                return false;
            }

            $position = $valuePosition + mb_strlen($decodedValue);
        }

        return true;
    }

    /**
     * @param  array<int, string>  $other
     */
    public function failureDescription($other): string
    {
        return sprintf('Failed asserting that \'%s\' contains "%s" in specified order.', $this->content, $this->failedValue);
    }

    public function toString(): string
    {
        return self::class;
    }
}
