<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Support\Normalize;

it('sorts class tokens so classes compare as an unordered set', function () {
    expect(Normalize::class('c a b'))->toBe(['a', 'b', 'c'])
        ->and(Normalize::class('  b  a  '))->toBe(['a', 'b'])
        ->and(Normalize::class(''))->toBe([]);
});

it('collapses whitespace and vertical space in text', function () {
    expect(Normalize::text('  hello   world  '))->toBe('hello world')
        ->and(Normalize::text("line 1\nline 2\r\nline 3"))->toBe('line 1line 2line 3')
        ->and(Normalize::text("tab\there"))->toBe('tab here');
});
