<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Constraints\SeeInOrder;

it('matches strings that appear in the given order', function () {
    $constraint = new SeeInOrder('alpha beta gamma');

    expect($constraint->evaluate(['alpha', 'beta', 'gamma'], '', true))->toBeTrue();
});

it('fails when strings are out of order', function () {
    $constraint = new SeeInOrder('alpha beta gamma');

    expect($constraint->evaluate(['gamma', 'alpha'], '', true))->toBeFalse();
});

it('skips empty needles', function () {
    $constraint = new SeeInOrder('alpha beta');

    expect($constraint->evaluate(['', 'alpha', '', 'beta'], '', true))->toBeTrue();
});

it('decodes HTML entities before matching', function () {
    $constraint = new SeeInOrder('foo &amp; bar');

    expect($constraint->evaluate(['foo & bar'], '', true))->toBeTrue();
});
