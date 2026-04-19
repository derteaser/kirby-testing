<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting;

use ArrayAccess;
use Derteaser\KirbyTesting\Concerns\AssertsDom;
use Derteaser\KirbyTesting\Concerns\AssertsStatusCodes;
use Derteaser\KirbyTesting\Constraints\SeeInOrder;
use Derteaser\KirbyTesting\TestResponseAssert as PHPUnit;
use Kirby\Http\Request;
use Kirby\Http\Response;
use LogicException;
use Throwable;

/**
 * @implements ArrayAccess<string, string>
 *
 * @phpstan-consistent-constructor
 */
class TestResponse implements ArrayAccess
{
    use AssertsDom;
    use AssertsStatusCodes;

    public ?Request $baseRequest;

    public Response $baseResponse;

    /**
     * @var list<Throwable>
     */
    public array $exceptions = [];

    public function __construct(Response $response, ?Request $request = null)
    {
        $this->baseResponse = $response;
        $this->baseRequest = $request;
    }

    public static function fromBaseResponse(Response $response, ?Request $request = null): static
    {
        return new static($response, $request);
    }

    public function assertSuccessful(): static
    {
        PHPUnit::withResponse($this)->assertTrue(
            $this->code() >= 200 && $this->code() < 300,
            $this->statusMessageWithDetails('>=200, <300', (int) $this->code()),
        );

        return $this;
    }

    public function assertSuccessfulPrecognition(): static
    {
        $this->assertNoContent();

        PHPUnit::withResponse($this)->assertTrue(
            (bool) $this->header('Precognition-Success'),
            'Header [Precognition-Success] not present on response.',
        );

        PHPUnit::withResponse($this)->assertSame(
            'true',
            $this->header('Precognition-Success'),
            'The Precognition-Success header was found, but the value is not `true`.',
        );

        return $this;
    }

    public function assertServerError(): static
    {
        PHPUnit::withResponse($this)->assertTrue(
            $this->code() >= 500 && $this->code() < 600,
            $this->statusMessageWithDetails('>=500, <600', (int) $this->code()),
        );

        return $this;
    }

    public function assertStatus(int $status): static
    {
        $actual = $this->code();

        PHPUnit::withResponse($this)->assertSame($status, $actual, $this->statusMessageWithDetails($status, (int) $actual));

        return $this;
    }

    protected function statusMessageWithDetails(int|string $expected, int|string $actual): string
    {
        return "Expected response status code [{$expected}] but received {$actual}.";
    }

    public function assertHeader(string $headerName, mixed $value = null): static
    {
        PHPUnit::withResponse($this)->assertNotNull(
            $this->header($headerName),
            "Header [{$headerName}] not present on response.",
        );

        if ($value !== null) {
            $actual = $this->header($headerName);
            PHPUnit::withResponse($this)->assertEquals(
                $value,
                $actual,
                "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}].",
            );
        }

        return $this;
    }

    public function assertHeaderMissing(string $headerName): static
    {
        PHPUnit::withResponse($this)->assertNull(
            $this->header($headerName),
            "Unexpected header [{$headerName}] is present on response.",
        );

        return $this;
    }

    public function assertDownload(?string $filename = null): static
    {
        $contentDisposition = explode(';', (string) $this->header('content-disposition'));

        if (trim($contentDisposition[0]) !== 'attachment') {
            PHPUnit::withResponse($this)->fail(
                'Response does not offer a file download.' . PHP_EOL .
                'Disposition [' . trim($contentDisposition[0]) . '] found in header, [attachment] expected.',
            );
        }

        if ($filename === null) {
            PHPUnit::withResponse($this)->assertTrue(true);

            return $this;
        }

        if (!isset($contentDisposition[1])) {
            PHPUnit::withResponse($this)->fail(
                sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename),
            );
        }

        $parts = explode('=', $contentDisposition[1]);

        if (trim($parts[0]) !== 'filename') {
            PHPUnit::withResponse($this)->fail(
                'Unsupported Content-Disposition header provided.' . PHP_EOL .
                'Disposition [' . trim($parts[0]) . '] found in header, [filename] expected.',
            );
        }

        PHPUnit::withResponse($this)->assertSame(
            $filename,
            isset($parts[1]) ? trim($parts[1], " \"'") : '',
            sprintf('Expected file [%s] is not present in Content-Disposition header.', $filename),
        );

        return $this;
    }

    public function assertContent(string $value): static
    {
        PHPUnit::withResponse($this)->assertSame($value, $this->body());

        return $this;
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertSee(array|string $value, bool $escape = true): static
    {
        $values = is_array($value) ? $value : [$value];

        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        foreach ($values as $needle) {
            PHPUnit::withResponse($this)->assertStringContainsString((string) $needle, $this->body());
        }

        return $this;
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertSeeHtml(array|string $value): static
    {
        return $this->assertSee($value, false);
    }

    /**
     * @param  array<int, string>  $values
     */
    public function assertSeeInOrder(array $values, bool $escape = true): static
    {
        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        PHPUnit::withResponse($this)->assertThat($values, new SeeInOrder($this->body()));

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     */
    public function assertSeeHtmlInOrder(array $values): static
    {
        return $this->assertSeeInOrder($values, false);
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertSeeText(array|string $value, bool $escape = true): static
    {
        $values = is_array($value) ? $value : [$value];

        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        $content = strip_tags($this->body());

        foreach ($values as $needle) {
            PHPUnit::withResponse($this)->assertStringContainsString((string) $needle, $content);
        }

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     */
    public function assertSeeTextInOrder(array $values, bool $escape = true): static
    {
        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        PHPUnit::withResponse($this)->assertThat($values, new SeeInOrder(strip_tags($this->body())));

        return $this;
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertDontSee(array|string $value, bool $escape = true): static
    {
        $values = is_array($value) ? $value : [$value];

        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        foreach ($values as $needle) {
            PHPUnit::withResponse($this)->assertStringNotContainsString((string) $needle, $this->body());
        }

        return $this;
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertDontSeeHtml(array|string $value): static
    {
        return $this->assertDontSee($value, false);
    }

    /**
     * @param  array<int, string>|string  $value
     */
    public function assertDontSeeText(array|string $value, bool $escape = true): static
    {
        $values = is_array($value) ? $value : [$value];

        if ($escape) {
            $values = array_map($this->escape(...), $values);
        }

        $content = strip_tags($this->body());

        foreach ($values as $needle) {
            PHPUnit::withResponse($this)->assertStringNotContainsString((string) $needle, $content);
        }

        return $this;
    }

    /**
     * @param  list<Throwable>  $exceptions
     */
    public function withExceptions(array $exceptions): static
    {
        $this->exceptions = $exceptions;

        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->baseResponse->{$key};
    }

    public function __isset(string $key): bool
    {
        return isset($this->baseResponse->{$key});
    }

    public function offsetExists($offset): bool
    {
        return false;
    }

    public function offsetGet($offset): string
    {
        return $this->baseResponse->body();
    }

    public function offsetSet($offset, mixed $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    public function code(): ?int
    {
        return $this->baseResponse->code();
    }

    public function header(string $key): ?string
    {
        return $this->baseResponse->header($key);
    }

    public function body(): string
    {
        return $this->baseResponse->body();
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
