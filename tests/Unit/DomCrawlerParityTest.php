<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;
use Symfony\Component\DomCrawler\Crawler;

/*
 * Gnarly-HTML parity check: exercises void elements, entity-escaped attributes,
 * and other parsing edge cases on the HTML5 parser (masterminds/html5, which
 * Symfony DomCrawler uses by default since 7.4). If these expectations ever
 * shift it means the parser upstream changed behaviour — investigate before
 * updating the fixtures.
 */

it('parses void elements and entity-escaped attributes consistently', function () {
    $html = file_get_contents(__DIR__ . '/../Fixtures/html/gnarly.html');

    if ($html === false) {
        throw new RuntimeException('Missing fixture: gnarly.html');
    }

    $crawler = new Crawler($html);
    $body = new AssertElement($crawler->filter('body')->first());

    $body->find('a', function (AssertElement $a): void {
        $a->has('href', '/path?x=1&y=2')->hasText('link with & entity');
    });

    $body->contains('img', 1);
    $body->contains('br');
    $body->contains('li', 2);
});
