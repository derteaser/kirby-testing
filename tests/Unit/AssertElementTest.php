<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\DomCrawler\Crawler;

function basicHtml(): string
{
    $html = file_get_contents(__DIR__ . '/../Fixtures/html/basic.html');

    if ($html === false) {
        throw new RuntimeException('Missing fixture: basic.html');
    }

    return $html;
}

function scopedTo(string $selector): AssertElement
{
    $crawler = (new Crawler(basicHtml()))->filter($selector)->first();

    return new AssertElement($crawler);
}

it('asserts element type via is()', function () {
    scopedTo('main h1')->is('h1');
});

it('asserts element type via magic isHN() dispatch', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('main h1')->isH1();
});

it('fails when element type does not match', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('main h1')->isForm();
})->throws(AssertionFailedError::class, 'not of type "form"');

it('matches attributes via has() and magic hasXxx() dispatch', function () {
    $link = scopedTo('a[href="/about"]');

    /** @phpstan-ignore-next-line */
    $link->hasHref('/about')
        ->hasAriaLabel('About us')
        ->hasDataTrack()
        ->hasClass('primary-btn btn');
});

it('handles boolean-only attributes via hasAttr() without a value', function () {
    $input = scopedTo('input[type="text"]');

    /** @phpstan-ignore-next-line */
    $input->hasRequired()->hasReadonly();
});

it('asserts text content with magic hasText()', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('main h1')->hasText('Welcome to the site');
});

it('fails when an expected attribute value is wrong', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('a[href="/about"]')->hasAriaLabel('Wrong');
})->throws(AssertionFailedError::class, 'Could not find an attribute "aria-label" with value "Wrong"');

it('finds descendants via find() and magic findXxx()', function () {
    $main = scopedTo('main');

    $main->find('a[href="/about"]', function (AssertElement $a): void {
        /** @phpstan-ignore-next-line */
        $a->hasText('About');
    });

    /** @phpstan-ignore-next-line */
    $main->findImg(function (AssertElement $img): void {
        $img->has('alt', 'Logo');
    });
});

it('asserts presence and count via contains() / magic containsXxx()', function () {
    $main = scopedTo('main');

    /** @phpstan-ignore-next-line */
    $main->containsA(2)
        ->containsA(['href' => '/about'])
        ->containsInput(['name' => 'q']);

    $main->contains('a', ['href' => '/contact', 'text' => 'Contact']);
});

it('asserts absence via doesntContain() / magic doesntContainXxx()', function () {
    $footer = scopedTo('footer');

    /** @phpstan-ignore-next-line */
    $footer->doesntContainForm()->doesntContainInput();
});

it('iterates with each()', function () {
    $count = 0;
    scopedTo('main')->each('a', function (AssertElement $a) use (&$count): void {
        $count++;
        /** @phpstan-ignore-next-line */
        $a->hasClass('btn');
    });

    expect($count)->toBe(2);
});

it('throws on undefined method', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('main')->bogus();
})->throws(BadMethodCallException::class);

it('matches classes as an unordered set', function () {
    /** @phpstan-ignore-next-line */
    scopedTo('main')->hasClass('main-wrap container');
});
