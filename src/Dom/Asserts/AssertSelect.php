<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts;

use PHPUnit\Framework\Assert;

final class AssertSelect extends BaseAssert
{
    public function containsOption(mixed $attributes): self
    {
        $this->contains('option', $attributes);

        return $this;
    }

    public function containsOptions(mixed ...$attributes): self
    {
        foreach ($attributes as $attribute) {
            $this->containsOption($attribute);
        }

        return $this;
    }

    public function containsOptgroup(mixed $attributes): self
    {
        $this->contains('optgroup', $attributes);

        return $this;
    }

    public function containsOptgroups(mixed ...$attributes): self
    {
        foreach ($attributes as $attribute) {
            $this->containsOptgroup($attribute);
        }

        return $this;
    }

    public function hasValue(mixed $value): self
    {
        $option = $this->filterOne('option[selected="selected"]');

        Assert::assertNotNull($option, 'No options are selected!');
        Assert::assertEquals($value, $option->attr('value'));

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     */
    public function hasValues(array $values): self
    {
        $options = $this->filterAll('option[selected="selected"]');

        Assert::assertGreaterThan(0, $options->count(), 'No options are selected!');

        $selected = [];
        foreach ($options as $index => $_) {
            $selected[] = $options->eq($index)->attr('value');
        }

        Assert::assertEqualsCanonicalizing($values, $selected, 'Selected values does not match');

        return $this;
    }
}
