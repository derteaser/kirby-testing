<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Support\CompareAttributes;

it('matches class attributes as unordered sets', function () {
    expect(CompareAttributes::compare('class', 'a b', 'b a'))->toBeTrue()
        ->and(CompareAttributes::compare('class', 'a', 'a b'))->toBeTrue()
        ->and(CompareAttributes::compare('class', 'a c', 'a b'))->toBeFalse();
});

it('matches required/readonly by presence regardless of value', function () {
    expect(CompareAttributes::compare('required', 'required', ''))->toBeTrue()
        ->and(CompareAttributes::compare('readonly', 'readonly', 'readonly'))->toBeTrue();
});

it('normalizes whitespace when comparing text/value attributes', function () {
    expect(CompareAttributes::compare('text', 'hello world', '  hello   world  '))->toBeTrue()
        ->and(CompareAttributes::compare('value', 'abc', 'abc'))->toBeTrue()
        ->and(CompareAttributes::compare('text', 'hello', 'world'))->toBeFalse();
});

it('defaults to strict equality for other attributes', function () {
    expect(CompareAttributes::compare('href', '/a', '/a'))->toBeTrue()
        ->and(CompareAttributes::compare('href', '/a', '/b'))->toBeFalse();
});

it('falls back to boolean-presence match when expected value is empty', function () {
    expect(CompareAttributes::compare('data-x', null, 'anything'))->toBeTrue();
});
