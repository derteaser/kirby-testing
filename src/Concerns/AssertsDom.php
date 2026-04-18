<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

use Closure;
use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;
use Derteaser\KirbyTesting\Dom\Asserts\AssertForm;
use Derteaser\KirbyTesting\TestResponse;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 *
 * @mixin TestResponse
 */
trait AssertsDom
{
    public function assertHtml5(): static
    {
        Assert::assertNotEmpty($this->body(), 'The view is empty!');

        $crawler = new Crawler($this->body());
        $doctype = $crawler->getNode(0)?->ownerDocument?->doctype;

        Assert::assertNotNull($doctype, 'Not a html5 doctype!');
        Assert::assertEquals('html', $doctype->name, 'Not a html5 doctype!');

        return $this;
    }

    public function assertElementExists(string|Closure $selector = 'body', ?Closure $callback = null): static
    {
        Assert::assertNotEmpty($this->body(), 'The view is empty!');

        if ($selector instanceof Closure) {
            $callback = $selector;
            $selector = 'body';
        }

        $crawler = new Crawler($this->body());
        $element = $crawler->filter($selector)->first();

        Assert::assertGreaterThan(
            0,
            $element->count(),
            sprintf('No element found with selector: %s', $selector),
        );

        if ($callback !== null) {
            $callback(new AssertElement($element));
        }

        return $this;
    }

    public function assertFormExists(string|Closure $selector = 'form', ?Closure $callback = null): static
    {
        Assert::assertNotEmpty($this->body(), 'The view is empty!');

        if ($selector instanceof Closure) {
            $callback = $selector;
            $selector = 'form';
        }

        $crawler = new Crawler($this->body());
        $form = $crawler->filter($selector)->first();

        Assert::assertGreaterThan(
            0,
            $form->count(),
            sprintf('No form was found with selector "%s"', $selector),
        );
        Assert::assertEquals('form', $form->nodeName(), 'Element is not of type form!');

        if ($callback !== null) {
            $callback(new AssertForm($form));
        }

        return $this;
    }
}
