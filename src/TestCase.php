<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use Kirby\Http\Request;
use Kirby\Http\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

/**
 * Base test case for feature-style tests against a Kirby 5 project.
 *
 * Subclasses must implement `roots()` to point at the project's fixtures and
 * storage. Each HTTP simulation (`get/post/put/patch/delete/call`) creates a
 * fresh `Kirby\Cms\App` with the requested method/URL baked into its request
 * props — no Apache, no front controller.
 *
 * Extension points for opt-in traits (e.g. InteractsWithBlade):
 *   - beforeApplicationCreation(array $props): called right before `new App(...)`,
 *     with the merged props the App is about to be constructed with
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

    /**
     * @param  array<string, mixed>  $props
     */
    public function createApplication(array $props = []): App
    {
        $props['roots'] = ($props['roots'] ?? []) + $this->roots($this->parallelCacheSuffix());

        $this->beforeApplicationCreation($props);

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
     * Hook fired immediately before `new App(...)` in every `createApplication()`
     * call, with the fully-merged props array the App is about to receive.
     * Default: no-op. Opt-in traits (e.g. InteractsWithBlade) override this.
     *
     * @param  array<string, mixed>  $props
     */
    protected function beforeApplicationCreation(array $props): void
    {
        // no-op
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

    /**
     * @return Pages<Page>
     */
    public function pages(): Pages
    {
        return $this->app->site()->pages();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function get(string $uri, array $parameters = []): TestResponse
    {
        return $this->call('GET', $uri, $parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
    public function post(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('POST', $uri, $parameters, $files, $content);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
    public function put(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('PUT', $uri, $parameters, $files, $content);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
    public function patch(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('PATCH', $uri, $parameters, $files, $content);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
    public function delete(string $uri, array $parameters = [], array $files = [], ?string $content = null): TestResponse
    {
        return $this->call('DELETE', $uri, $parameters, $files, $content);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $files
     */
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

        $response = $this->app->render($uri, $method);

        if ($response === null) {
            throw new RuntimeException(sprintf('Kirby did not produce a response for [%s %s].', $method, $uri));
        }

        return $this->createTestResponse($response, $this->app->request());
    }

    protected function createTestResponse(Response $response, Request $request): TestResponse
    {
        return TestResponse::fromBaseResponse($response, $request);
    }
}
