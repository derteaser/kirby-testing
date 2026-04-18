<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionProperty;
use Throwable;

/**
 * Decorates PHPUnit assertion failures with context from the TestResponse
 * (notably any exception Kirby logged during the request that triggered this
 * assertion).
 *
 * @mixin Assert
 */
final class TestResponseAssert
{
    private function __construct(private TestResponse $response)
    {
    }

    public static function withResponse(TestResponse $response): self
    {
        return new self($response);
    }

    /**
     * @throws ExpectationFailedException
     */
    public function __call(string $name, array $arguments): void
    {
        try {
            Assert::$name(...$arguments);
        } catch (ExpectationFailedException $e) {
            throw $this->injectResponseContext($e);
        }
    }

    public static function __callStatic(string $name, array $arguments): void
    {
        Assert::$name(...$arguments);
    }

    private function injectResponseContext(ExpectationFailedException $exception): ExpectationFailedException
    {
        $exceptions = $this->response->exceptions;

        if ($exceptions === []) {
            return $exception;
        }

        return $this->appendExceptionToException(end($exceptions), $exception);
    }

    private function appendExceptionToException(
        Throwable $exceptionToAppend,
        ExpectationFailedException $exception,
    ): ExpectationFailedException {
        $exceptionMessage = $exceptionToAppend->getMessage();
        $exceptionString = (string) $exceptionToAppend;

        $message = <<<EOF
        The following exception occurred during the last request:

        $exceptionString

        ----------------------------------------------------------------------------------

        $exceptionMessage
        EOF;

        return $this->appendMessageToException($message, $exception);
    }

    private function appendMessageToException(string $message, ExpectationFailedException $exception): ExpectationFailedException
    {
        $property = new ReflectionProperty($exception, 'message');
        $property->setValue($exception, $exception->getMessage() . PHP_EOL . PHP_EOL . $message . PHP_EOL);

        return $exception;
    }
}
