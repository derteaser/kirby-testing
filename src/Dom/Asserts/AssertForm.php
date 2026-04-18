<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts;

use Closure;
use PHPUnit\Framework\Assert;

final class AssertForm extends BaseAssert
{
    public function hasAction(string $action): self
    {
        $actual = $this->normalizeAction((string) $this->getAttribute('action'));
        $expected = $this->normalizeAction($action);

        Assert::assertEquals(
            $expected,
            $actual,
            sprintf('Could not find an action on the form with the value %s', $action),
        );

        return $this;
    }

    public function hasMethod(string $method): self
    {
        if (!in_array(strtolower($method), ['get', 'post'], true)) {
            return $this->hasSpoofMethod($method);
        }

        Assert::assertEquals(
            strtolower($method),
            strtolower((string) $this->getAttribute('method')),
            sprintf('Could not find a method on the form with the value %s', $method),
        );

        return $this;
    }

    public function hasSpoofMethod(string $type): self
    {
        $element = $this->filterOne('input[type="hidden"][name="_method"]');

        Assert::assertNotNull($element, 'No spoof methods was found in form!');
        Assert::assertEquals(
            strtolower($type),
            strtolower((string) $element->attr('value')),
            sprintf('No spoof method for %s was found in form!', $type),
        );

        return $this;
    }

    public function hasCSRF(): self
    {
        Assert::assertNotNull(
            $this->filterOne('input[type="hidden"][name="_token"]'),
            'No CSRF was found in form!',
        );

        return $this;
    }

    public function findSelect(string|Closure $selector = 'select', ?Closure $callback = null): self
    {
        if ($selector instanceof Closure) {
            $callback = $selector;
            $selector = 'select';
        }

        $select = $this->filterOne($selector);

        if ($select === null) {
            Assert::fail(sprintf('No select found for selector: %s', $selector));
        }

        if ($callback !== null) {
            $callback(new AssertSelect($select));
        }

        return $this;
    }

    public function findDatalist(string|Closure $selector = 'datalist', ?Closure $callback = null): self
    {
        if ($selector instanceof Closure) {
            $callback = $selector;
            $selector = 'datalist';
        }

        if ($selector !== 'datalist' && $selector[0] !== '#') {
            Assert::fail(sprintf('Selectors for datalists must be an id, given: %s', $selector));
        }

        $datalist = $this->filterOne($selector);

        if ($datalist === null) {
            Assert::fail(sprintf('No datalist found for datalist: %s', $selector));
        }

        if ($callback !== null) {
            $callback(new AssertDatalist($datalist));
        }

        return $this;
    }

    private function normalizeAction(string $value): string
    {
        $value = strtolower($value);
        $value = '/' . ltrim($value, '/');

        return rtrim($value, '/') . '/';
    }
}
