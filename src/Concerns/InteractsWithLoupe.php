<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

use Closure;
use Kirby\Cms\App;
use LogicException;

/**
 * Opt-in: caches a Loupe search index across the tests in a worker process.
 *
 * The package doesn't ship with a specific Loupe wrapper because search index
 * construction is always project code. Register a factory in `tests/Pest.php`:
 *
 *     \Derteaser\KirbyTesting\Concerns\InteractsWithLoupe::setSearchIndexFactory(
 *         fn(\Kirby\Cms\App $app) => tap(new \App\Search\LoupeSearch(true))->index($app)
 *     );
 *
 * Then consume it in feature tests via `$this->searchIndex()`.
 *
 * Cache scope is intentionally per-process (static property). ParaTest runs
 * one worker per process, so each worker gets its own index — safe under
 * `pest --parallel`. Do NOT flip to a thread-based runner without revisiting.
 *
 * @property App $app
 */
trait InteractsWithLoupe
{
    private static mixed $cachedSearchIndex = null;

    private static ?Closure $searchIndexFactory = null;

    public static function setSearchIndexFactory(Closure $factory): void
    {
        self::$searchIndexFactory = $factory;
        self::$cachedSearchIndex = null;
    }

    public function searchIndex(): mixed
    {
        if (self::$cachedSearchIndex !== null) {
            return self::$cachedSearchIndex;
        }

        $factory = self::$searchIndexFactory
            ?? throw new LogicException(
                sprintf(
                    '%s: no search index factory registered. '
                        . 'Call %s::setSearchIndexFactory(fn(Kirby\\Cms\\App $app) => ...) in tests/Pest.php first.',
                    static::class,
                    self::class,
                ),
            );

        return self::$cachedSearchIndex = $factory($this->app);
    }
}
