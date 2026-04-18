<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts;

use BadMethodCallException;
use Derteaser\KirbyTesting\Dom\Asserts\Concerns\Debugging;
use Derteaser\KirbyTesting\Dom\Asserts\Concerns\GathersAttributes;
use Derteaser\KirbyTesting\Dom\Asserts\Concerns\UsesElementAsserts;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Fluent DOM assertion scoped to a single node (or the document root).
 *
 * Backed by symfony/dom-crawler on the default libxml parser — masterminds/html5
 * is deliberately not pulled in, so behavior matches the historical DOMDocument
 * pipeline this class replaced.
 */
abstract class BaseAssert
{
    use Debugging;
    use GathersAttributes;
    use UsesElementAsserts;

    protected Crawler $crawler;

    /**
     * @var array<string, list<array<string, string|null>>>
     */
    protected array $attributes = [];

    /**
     * @param string|Crawler $source Raw HTML to parse, or an already-scoped Crawler.
     */
    public function __construct(string|Crawler $source)
    {
        $this->crawler = $source instanceof Crawler ? $source : new Crawler($source);
    }

    /**
     * Magic dispatch so feature tests can read naturally:
     *   $el->hasAriaLabel('Submit')
     *   $el->isForm()
     *   $el->findButton(fn($btn) => $btn->hasType('submit'))
     *   $el->containsInput(['type' => 'text'])
     *   $el->doesntContainScript()
     *
     * Dispatch order matters: `doesntContain` must resolve before `contains`
     * because both share the letters in a different arrangement (the prefix
     * check below uses str_starts_with, so ordering is done by listing
     * `doesntContain` first).
     */
    public function __call(string $method, array $arguments): self
    {
        foreach (['doesntContain', 'contains', 'find', 'has', 'is'] as $prefix) {
            if (!str_starts_with($method, $prefix)) {
                continue;
            }

            $suffix = substr($method, strlen($prefix));

            if ($suffix === '') {
                continue;
            }

            return match ($prefix) {
                'has' => $this->has($this->hyphenate($suffix), $arguments[0] ?? null),
                'is' => $this->is($this->hyphenate($suffix)),
                'find' => $this->find($this->hyphenate($suffix), $arguments[0] ?? null),
                'contains' => $this->contains(lcfirst($suffix), ...$arguments),
                'doesntContain' => $this->doesntContain(lcfirst($suffix), ...$arguments),
            };
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }

    /**
     * 'AriaLabel' → 'aria-label', 'Id' → 'id', 'DataFooBar' → 'data-foo-bar'.
     */
    private function hyphenate(string $name): string
    {
        return strtolower((string) preg_replace('/(?<=\w)([A-Z])/', '-$1', $name));
    }

    protected function filterOne(string $selector): ?Crawler
    {
        $result = $this->crawler->filter($selector)->first();

        return $result->count() > 0 ? $result : null;
    }

    protected function filterAll(string $selector): Crawler
    {
        return $this->crawler->filter($selector);
    }

    protected function getAttribute(string $attribute): ?string
    {
        if ($this->crawler->count() === 0) {
            return null;
        }

        if ($attribute === 'text') {
            return $this->crawler->text(null, false);
        }

        return $this->crawler->attr($attribute);
    }

    protected function hasAttribute(string $attribute): bool
    {
        $node = $this->crawler->getNode(0);

        return $node instanceof DOMElement && $node->hasAttribute($attribute);
    }

    protected function getType(): string
    {
        return $this->crawler->nodeName();
    }

    protected function getOuterHtml(): string
    {
        if ($this->crawler->count() === 0) {
            return '';
        }

        return $this->crawler->outerHtml();
    }

    /**
     * @internal Exposed for AssertForm / AssertSelect so they can share the
     *           scoped Crawler without re-parsing the HTML string.
     */
    public function crawler(): Crawler
    {
        return $this->crawler;
    }
}
