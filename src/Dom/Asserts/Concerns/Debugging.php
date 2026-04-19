<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts\Concerns;

use Derteaser\KirbyTesting\Dom\Support\HtmlFormatter;

/**
 * @internal
 */
trait Debugging
{
    public function dump(): self
    {
        $formatted = (new HtmlFormatter)->format($this->getOuterHtml());

        if (function_exists('dump')) {
            dump($formatted);
        } else {
            fwrite(STDERR, $formatted . PHP_EOL);
        }

        return $this;
    }

    abstract protected function getOuterHtml(): string;
}
