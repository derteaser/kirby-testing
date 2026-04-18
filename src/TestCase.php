<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for feature-style tests against a Kirby 5 project.
 *
 * Subclasses must implement `roots()` to point at the project's fixtures and
 * storage. Each HTTP simulation (`get/post/put/patch/delete/call`) creates a
 * fresh `Kirby\Cms\App` with the requested method/URL baked into its request
 * props — no Apache, no front controller.
 *
 * Extension points for opt-in traits (e.g. InteractsWithBlade):
 *   - afterApplicationCreated(App $app): called at the end of createApplication()
 */
abstract class TestCase extends BaseTestCase
{
    public App $app;

    protected function setUp(): void
    {
        App::$enableWhoops = false;

        $this->app = $this->createApplication();
    }

    public function createApplication(array $props = []): App
    {
        $props['roots'] = ($props['roots'] ?? []) + $this->roots($this->parallelCacheSuffix());

        $app = new App($props);

        $this->afterApplicationCreated($app);

        return $app;
    }

    /**
     * Return the Kirby `roots` array for the consuming project.
     *
     * The `$cacheSuffix` argument is the per-worker cache scope under
     * `pest --parallel` — append it to the `cache` root to avoid the
     * Blade compiled-views race that otherwise produces ViewExceptions.
     *
     * @return array<string, string>
     */
    abstract protected function roots(string $cacheSuffix): array;

    /**
     * Per-worker cache path suffix for `pest --parallel`. Returns `''`
     * outside parallel runs. Consumers call this from `roots()`.
     */
    protected function parallelCacheSuffix(): string
    {
        $token = getenv('TEST_TOKEN');

        return is_string($token) && ctype_digit($token) ? '/parallel/' . $token : '';
    }

    /**
     * Hook fired after `new App(...)` in every `createApplication()` call.
     * Default: no-op. Opt-in traits (e.g. InteractsWithBlade) override this.
     */
    protected function afterApplicationCreated(App $app): void
    {
        // no-op
    }

    public function site(): Site
    {
        return $this->app->site();
    }

    public function page(?string $id = null, Page|Site|null $parent = null, bool $drafts = true): ?Page
    {
        return $this->app->page($id, $parent, $drafts);
    }

    public function pages(): Pages
    {
        return $this->app->site()->pages();
    }

    public function get(string $uri, array $parameters = []): TestResponse
    {
        return $this->call('GET', $uri, $parameters);
    }

    public function post(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('POST', $uri, $parameters, $files, $content);
    }

    public function put(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('PUT', $uri, $parameters, $files, $content);
    }

    public function patch(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('PATCH', $uri, $parameters, $files, $content);
    }

    public function delete(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('DELETE', $uri, $parameters, $files, $content);
    }

    public function call(string $method, string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        $this->app = $this->createApplication([
            'request' => [
                'method' => $method,
                'url' => $uri,
                'query' => $parameters,
                'files' => $files,
                'body' => $content,
            ],
        ]);

        return $this->createTestResponse($this->app->render($uri, $method), $this->app->request());
    }

    protected function createTestResponse($response, $request): TestResponse
    {
        return TestResponse::fromBaseResponse($response, $request);
    }
}
