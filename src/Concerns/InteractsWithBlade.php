<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Kirby\Cms\App;

/**
 * Opt-in: registers Laravel's `__components` Blade namespace eagerly so inline
 * component compilation doesn't race under `pest --parallel`.
 *
 * Background: Laravel lazily registers this namespace on the first inline
 * component render. Kirby apps created per-request rebuild Blade's View
 * Factory, so workers that don't trip the registration path in the right
 * order hit "No hint path defined for [__components]". Calling this at the
 * end of every `createApplication()` is idempotent and cheap.
 *
 * No-op if neither `illuminate/container` nor a configured compiled-views
 * path are present.
 */
trait InteractsWithBlade
{
    protected function afterApplicationCreated(App $app): void
    {
        parent::afterApplicationCreated($app);

        $this->primeBladeComponentsNamespace();
    }

    private function primeBladeComponentsNamespace(): void
    {
        if (!class_exists(IlluminateContainer::class)) {
            return;
        }

        $container = IlluminateContainer::getInstance();

        if (!$container->bound(ViewFactoryContract::class) || !$container->bound('config')) {
            return;
        }

        $compiled = $container['config']->get('view.compiled');

        if (!is_string($compiled) || $compiled === '') {
            return;
        }

        $container->make(ViewFactoryContract::class)->addNamespace('__components', $compiled);
    }
}
