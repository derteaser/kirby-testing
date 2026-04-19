<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts\Concerns;

use Closure;
use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;
use Derteaser\KirbyTesting\Dom\Support\CompareAttributes;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
trait UsesElementAsserts
{
    public function has(string $attribute, mixed $value = null): self
    {
        if (!$value) {
            Assert::assertTrue($this->hasAttribute($attribute), sprintf('Could not find an attribute "%s"', $attribute));

            return $this;
        }

        $found = $this->getAttribute($attribute);

        Assert::assertTrue(
            CompareAttributes::compare($attribute, $value, $found),
            sprintf('Could not find an attribute "%s" with value "%s". "%s" found', $attribute, $value, trim((string) $found)),
        );

        return $this;
    }

    public function doesntHave(string $attribute, mixed $value = null): self
    {
        if (!$value) {
            Assert::assertFalse($this->hasAttribute($attribute), sprintf('Found an attribute "%s"', $attribute));

            return $this;
        }

        Assert::assertFalse(
            CompareAttributes::compare($attribute, $value, $this->getAttribute($attribute)),
            sprintf('Found an attribute "%s" with value "%s"', $attribute, $value),
        );

        return $this;
    }

    public function find(string $selector, ?Closure $callback = null): self
    {
        $element = $this->filterOne($selector);

        Assert::assertNotNull(
            $element,
            sprintf('Could not find any matching element for selector "%s"', $selector),
        );

        if ($callback !== null) {
            $callback(new AssertElement($element));
        }

        return $this;
    }

    public function each(string $selector, Closure $callback): self
    {
        $elements = $this->filterAll($selector);

        Assert::assertGreaterThan(
            0,
            $elements->count(),
            sprintf('Could not find any matching element for selector "%s"', $selector),
        );

        foreach ($elements as $index => $_) {
            $callback(new AssertElement($elements->eq($index)));
        }

        return $this;
    }

    public function contains(string $selector, mixed $attributes = null, int $count = 0): self
    {
        Assert::assertNotNull(
            $this->filterOne($selector),
            sprintf('Could not find any matching element of type "%s"', $selector),
        );

        if (is_numeric($attributes)) {
            $count = (int) $attributes;
            $attributes = null;
        }

        if (!$attributes && !$count) {
            return $this;
        }

        if (!$attributes) {
            $found = $this->filterAll($selector)->count();
            Assert::assertEquals(
                $count,
                $found,
                sprintf('Expected to find %s elements but found %s for %s', $count, $found, $selector),
            );

            return $this;
        }

        $this->gatherAttributes($selector);

        if ($count) {
            $matches = 0;
            foreach ($this->attributes[$selector] as $foundAttributes) {
                if ($this->compareAttributesArrays($attributes, $foundAttributes)) {
                    $matches++;
                }
            }

            Assert::assertEquals(
                $count,
                $matches,
                sprintf('Expected to find %s elements but found %s for %s', $count, $matches, $selector),
            );
        }

        $matched = false;
        foreach ($this->attributes[$selector] as $foundAttributes) {
            if ($this->compareAttributesArrays($attributes, $foundAttributes)) {
                $matched = true;
                break;
            }
        }

        Assert::assertTrue(
            $matched,
            sprintf('Could not find a matching "%s" with data: %s', $selector, json_encode($attributes, JSON_PRETTY_PRINT)),
        );

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function doesntContain(string $elementName, array $attributes = []): self
    {
        if (!$attributes) {
            Assert::assertNull(
                $this->filterOne($elementName),
                sprintf('Found a matching element of type "%s"', $elementName),
            );

            return $this;
        }

        $this->gatherAttributes($elementName);

        $matched = false;
        foreach ($this->attributes[$elementName] as $foundAttributes) {
            if ($this->compareAttributesArrays($attributes, $foundAttributes)) {
                $matched = true;
                break;
            }
        }

        Assert::assertFalse(
            $matched,
            sprintf('Found a matching "%s" with data: %s', $elementName, json_encode($attributes, JSON_PRETTY_PRINT)),
        );

        return $this;
    }

    public function containsText(string $needle, bool $ignoreCase = false): self
    {
        $text = (string) $this->getAttribute('text');

        $method = $ignoreCase ? 'assertStringContainsStringIgnoringCase' : 'assertStringContainsString';

        Assert::$method(
            $needle,
            $text,
            sprintf('Could not find text content "%s" containing %s', $text, $needle),
        );

        return $this;
    }

    public function doesntContainText(string $needle, bool $ignoreCase = false): self
    {
        $text = (string) $this->getAttribute('text');

        $method = $ignoreCase ? 'assertStringNotContainsStringIgnoringCase' : 'assertStringNotContainsString';

        Assert::$method(
            $needle,
            $text,
            sprintf('Found text content "%s" containing %s', $text, $needle),
        );

        return $this;
    }

    public function is(string $type): self
    {
        Assert::assertEquals(
            $type,
            $this->getType(),
            sprintf('Element is not of type "%s"', $type),
        );

        return $this;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, string|null>  $actual
     */
    private function compareAttributesArrays(array $expected, array $actual): bool
    {
        foreach ($expected as $attribute => $value) {
            if (!array_key_exists($attribute, $actual)) {
                return false;
            }

            if (!CompareAttributes::compare($attribute, $value, $actual[$attribute])) {
                return false;
            }
        }

        return true;
    }

    abstract protected function filterOne(string $selector): ?Crawler;

    abstract protected function filterAll(string $selector): Crawler;

    abstract protected function getAttribute(string $attribute): ?string;

    abstract protected function hasAttribute(string $attribute): bool;

    abstract protected function getType(): string;
}
