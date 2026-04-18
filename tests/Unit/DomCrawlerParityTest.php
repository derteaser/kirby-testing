<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;
use Symfony\Component\DomCrawler\Crawler;

/*
 * Parity check: deliberately ugly HTML that historically exercises edge cases
 * in libxml parsing. If masterminds/html5 ever sneaks into the graph, the
 * parser semantics could drift — this file is the canary.
 *
 * Do not add `masterminds/html5` as a dependency without updating these
 * expectations deliberately.
 */

it('parses void elements and entity-escaped attributes consistently', function () {
    $html = file_get_contents(__DIR__ . '/../Fixtures/html/gnarly.html');
    $crawler = new Crawler($html);
    $body = new AssertElement($crawler->filter('body')->first());

    $body->find('a', function (AssertElement $a): void {
        $a->has('href', '/path?x=1&y=2')->hasText('link with & entity');
    });

    $body->contains('img', 1);
    $body->contains('br');
    $body->contains('li', 2);
});
