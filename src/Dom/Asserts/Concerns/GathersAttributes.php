<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts\Concerns;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
trait GathersAttributes
{
    /**
     * Collect attribute bags for all descendants matching `$selector`, keyed
     * by selector so repeated calls hit a per-instance cache. A synthetic
     * `text` key carries nodeValue; `<textarea>` nodes also expose their
     * text under `value` so forms can be asserted as a flat attribute bag.
     */
    public function gatherAttributes(string $selector): void
    {
        if (isset($this->attributes[$selector])) {
            return;
        }

        $this->attributes[$selector] = [];

        foreach ($this->filterAll($selector) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $attributes = [];

            foreach ($node->attributes as $attribute) {
                $attributes[$attribute->nodeName] = $attribute->value;
            }

            $attributes['text'] = $node->nodeValue;

            if ($selector === 'textarea') {
                $attributes['value'] = $node->nodeValue;
            }

            $this->attributes[$selector][] = $attributes;
        }
    }

    abstract protected function filterAll(string $selector): Crawler;
}
