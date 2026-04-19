<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Support\Facades\Facade;
use Kirby\Cms\App;

/**
 * Opt-in: covers two Blade-in-tests hazards that bite when a consumer creates
 * multiple Kirby Apps per process (setUp + each get/post/... call does so).
 *
 * 1) Before every new App, clear Laravel's Facade resolved-instance cache.
 *    Facade caches the first-resolved backing service statically, so once
 *    `Blade::directive()` has touched BladeCompiler #1 (App #1 boot),
 *    any later `Blade::directive()` / `Blade::if()` call — including the
 *    ones a Blade-bootstrapping plugin fires from `system.loadPlugins:after`
 *    while App #2 boots — keeps targeting BC #1. BC #2 then ends up with
 *    zero custom directives, and templates that use them (e.g. `@isajax`
 *    paired with `@else`) compile to broken PHP (orphan `else`).
 *
 * 2) After every new App, eagerly register Laravel's `__components` Blade
 *    namespace. Laravel lazily registers it on the first inline component
 *    render; per-App rebuilds of the View Factory mean workers that don't
 *    trip the registration path in the right order hit
 *    "No hint path defined for [__components]". Idempotent and cheap.
 *
 * Both guards are no-ops if the relevant Illuminate classes aren't loaded.
 */
trait InteractsWithBlade
{
    /**
     * @param  array<string, mixed>  $props
     */
    protected function beforeApplicationCreation(array $props): void
    {
        parent::beforeApplicationCreation($props);

        $this->clearBladeFacadeCache();
    }

    protected function afterApplicationCreated(App $app): void
    {
        parent::afterApplicationCreated($app);

        $this->primeBladeComponentsNamespace();
    }

    private function clearBladeFacadeCache(): void
    {
        if (!class_exists(Facade::class)) {
            return;
        }

        Facade::clearResolvedInstances();
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
