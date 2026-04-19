# CLAUDE.md

Notes for Claude Code working in this repository.

## Project

Reusable Composer package providing fluent HTTP + DOM test assertions for Kirby 5 projects. Extracted from `blaue-funken-kirby` so the same infrastructure can be shared across multiple Kirby projects.

Consumers extend `Derteaser\KirbyTesting\TestCase` and get `$this->get('/uri')` + chainable DOM assertions on the response.

## Commands

```bash
composer install         # Install dependencies (allows getkirby/composer-installer and pest plugin)
composer test            # Runs vendor/bin/pest
vendor/bin/pest tests/Unit/AssertElementTest.php   # Single test file
vendor/bin/pest --filter="hyphenates"              # Single test by description
```

## Architecture

### `src/` (PSR-4: `Derteaser\KirbyTesting\`)

- **`TestCase.php`** — Abstract base. Subclasses override `roots(string $cacheSuffix): array`. `afterApplicationCreated(App $app)` is the extension hook opt-in traits plug into.
- **`TestResponse.php`** — Wraps `Kirby\Cms\Response` + `Kirby\Http\Request`. Deliberately does not depend on `Kirby\Toolkit\*` or `illuminate/support` — uses vanilla PHP (`is_array`, `htmlspecialchars`) so the surface stays small.
- **`TestResponseAssert.php`** — Decorates PHPUnit failures with context from the logged exceptions on the response.
- **`Concerns/`** — Opt-in traits consumers mix into their `TestCase`:
  - `AssertsStatusCodes` / `AssertsDom` — Always on (used by `TestResponse`).
  - `InteractsWithBlade` — Primes Laravel's `__components` namespace for parallel testing. No-op if `illuminate/container` isn't present.
  - `InteractsWithLoupe` — Static-cached search index via a consumer-registered factory. Throws if no factory set.
  - `SuppressesExifWarnings` — Swallows `exif_read_data: File not supported` warnings.
- **`Constraints/SeeInOrder.php`** — PHPUnit constraint used by `TestResponse::assertSeeInOrder`.
- **`Dom/`** — DOM assertion layer built on `symfony/dom-crawler`:
  - `Asserts/BaseAssert.php` — The hinge. Magic `__call` dispatches `hasAriaLabel` → `has('aria-label')`, `isForm` → `is('form')`, `findButton` → `find('button')`, `containsInput` → `contains('input')`, `doesntContainScript` → `doesntContain('script')`. Implemented with vanilla `preg_replace`, no `Illuminate\Support\Str`.
  - `Asserts/AssertElement.php`, `AssertForm.php`, `AssertSelect.php`, `AssertDatalist.php` — Specializations with form/option helpers.
  - `Asserts/Concerns/{UsesElementAsserts,GathersAttributes,Debugging}.php` — Shared behaviour.
  - `Support/{Normalize,CompareAttributes,HtmlFormatter}.php` — Class-token sorting, matcher dispatch, debug dump formatter.
  - `Matchers/{Values,NoValues,Classes,Text}.php` — Attribute comparison strategies. `NoValues` is what makes boolean-only attrs like `required`/`readonly` match by presence.

### `tests/` (PSR-4: `Derteaser\KirbyTesting\Tests\`)

- Pure unit tests. No Kirby `App` is booted.
- `tests/Fixtures/html/*.html` holds the HTML fixtures the DOM-assertion tests drive.
- `TestResponseTest` constructs a `Kirby\Cms\Response` directly — the `getkirby/cms` dep is only for class resolution, not a running app.

## Conventions

- **Target PHP 8.3**, `declare(strict_types=1)` at the top of every source file. The package supports `^8.3 || ^8.4 || ^8.5`, so avoid PHP 8.4-only language features (asymmetric visibility, property hooks, `array_find`/`array_any`, new `mb_*` helpers, `#[\Deprecated]`, lazy-objects API). First-class callable syntax (`$this->foo(...)`) is PHP 8.1+ and fine.
- **4 spaces, single quotes, 140 char width.** Same Prettier profile as the consumer project — expect a PostToolUse hook to reformat on save.
- **No `illuminate/support` dependency.** Any code you write must use vanilla PHP or the already-imported libraries (PHPUnit, Symfony DomCrawler/CssSelector). Laravel helpers (`Str::`, `collect()`, `e()`) are out.
- **`masterminds/html5` is an explicit require (`^2.10`).** Symfony 7.4+ DomCrawler pulls it in anyway and makes the HTML5 parser the default in `new Crawler($html)`; the explicit pin keeps the floor free of PHP 8.4 nullability deprecations from html5 < 2.10. If you ever need libxml-only behaviour, pass `$useHtml5Parser: false` to the Crawler constructor — but the consumer suite passes with HTML5 parsing, so prefer not to.
- **Symfony peer-dep**: `symfony/dom-crawler` and `symfony/css-selector` are `^7.4 || ^8.0`. The Symfony 8 breaking change to note is that `Crawler::__construct`'s `$useHtml5Parser` argument is gone — don't pass it.
- **Kirby peer-dep**: `getkirby/cms: ^5.0`. Keep `TestResponse` / `TestCase` coupled to `Kirby\Cms\App`, `Kirby\Cms\Response`, `Kirby\Http\Request` only. Do not reach into `Kirby\Toolkit\*` again.

## Notable invariants to preserve

- `TestCase::parallelCacheSuffix()` must return `'/parallel/<token>'` under `pest --parallel` — consumers depend on this to isolate `storage/cache/views/` per ParaTest worker.
- `InteractsWithLoupe` caches in a static property. This is correct under ParaTest's one-process-per-worker model. Do not switch to a thread-based runner without revisiting.
- `BaseAssert::__call` dispatch order: `doesntContain` must be matched before `contains` because `str_starts_with('doesntContainX', 'contains')` is false but the listing order in the method affects which prefix wins when names share a structure. The current order is `['doesntContain', 'contains', 'find', 'has', 'is']`.
- The `AssertForm::hasAction` normalisation (`/…/`, lowercase) is load-bearing for the consumer's form tests. Don't tighten the comparator.

## Consuming project

Currently consumed by `../blaue-funken-kirby/` via a Composer `path` repo (symlink). ADR-0003 in that repo documents why the testing infrastructure was split out and how the consumer's `tests/TestCase.php` wires it up.
