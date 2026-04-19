# derteaser/kirby-testing

Fluent HTTP + DOM test assertions for Kirby 5 projects, built on Pest/PHPUnit and `symfony/dom-crawler`.

## Install

```bash
composer require --dev derteaser/kirby-testing
```

## Usage

Extend the base `TestCase` and provide roots for your fixtures:

```php
<?php

namespace Tests;

use Derteaser\KirbyTesting\Concerns\InteractsWithBlade;
use Derteaser\KirbyTesting\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithBlade; // opt-in, only if your project uses leitsch/kirby-blade

    protected function roots(string $cacheSuffix): array
    {
        $base    = dirname(__DIR__);
        $storage = $base . '/storage';

        return [
            'index'    => $base . '/public',
            'base'     => $base,
            'content'  => __DIR__ . '/content',
            'site'     => $base . '/site',
            'storage'  => $storage,
            'accounts' => $storage . '/accounts',
            'cache'    => $storage . '/cache' . $cacheSuffix,
            'sessions' => $storage . '/sessions',
        ];
    }
}
```

Wire `tests/Pest.php`:

```php
<?php

require_once __DIR__ . '/../kirby/bootstrap.php';

\Derteaser\KirbyTesting\Concerns\SuppressesExifWarnings::register();

pest()->extend(Tests\TestCase::class)->in('Feature');
```

Write tests:

```php
use Derteaser\KirbyTesting\Dom\Asserts\AssertElement;

it('renders the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertElementExists('main h1', function (AssertElement $h1) {
            $h1->hasText('Welcome');
        });
});
```

## Opt-in concerns

- `InteractsWithBlade` — primes Laravel's `__components` namespace so inline Blade components render under `pest --parallel`. No-op if `illuminate/container` isn't present.
- `InteractsWithLoupe` — static-cached search index via a factory closure registered from `Pest.php`. Requires `loupe/loupe` at the consumer side.
- `SuppressesExifWarnings` — swallows `exif_read_data: File not supported` warnings that Kirby emits when probing non-JPEG fixtures. Delegates other warnings to the previous handler.

## Behavioral notes

- The DOM assertion layer uses `symfony/dom-crawler` with its default HTML5 parser (`masterminds/html5`), which matches how browsers parse real-world markup.
- `Normalize::class()` compares class attributes as unordered sets.
- Boolean-only attributes (`required`, `readonly`, `disabled`, …) are matched by presence, not value.
- `Normalize::text()` collapses whitespace and vertical space when comparing text.

## Requirements

- PHP 8.3, 8.4, or 8.5
- Kirby 5.x
- Symfony DomCrawler/CssSelector 7.4 or 8
- PHPUnit 11, 12, or 13
