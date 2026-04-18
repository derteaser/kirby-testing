<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

use Derteaser\KirbyTesting\TestResponse;
use PHPUnit\Framework\Assert;

/**
 * @mixin TestResponse
 */
trait AssertsStatusCodes
{
    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): static
    {
        return $this->assertStatus(201);
    }

    public function assertAccepted(): static
    {
        return $this->assertStatus(202);
    }

    public function assertNoContent(int $status = 204): static
    {
        $this->assertStatus($status);

        Assert::assertEmpty($this->body(), 'Response content is not empty.');

        return $this;
    }

    public function assertMovedPermanently(): static
    {
        return $this->assertStatus(301);
    }

    public function assertFound(): static
    {
        return $this->assertStatus(302);
    }

    public function assertNotModified(): static
    {
        return $this->assertStatus(304);
    }

    public function assertTemporaryRedirect(): static
    {
        return $this->assertStatus(307);
    }

    public function assertPermanentRedirect(): static
    {
        return $this->assertStatus(308);
    }

    public function assertBadRequest(): static
    {
        return $this->assertStatus(400);
    }

    public function assertUnauthorized(): static
    {
        return $this->assertStatus(401);
    }

    public function assertPaymentRequired(): static
    {
        return $this->assertStatus(402);
    }

    public function assertForbidden(): static
    {
        return $this->assertStatus(403);
    }

    public function assertNotFound(): static
    {
        return $this->assertStatus(404);
    }

    public function assertMethodNotAllowed(): static
    {
        return $this->assertStatus(405);
    }

    public function assertNotAcceptable(): static
    {
        return $this->assertStatus(406);
    }

    public function assertRequestTimeout(): static
    {
        return $this->assertStatus(408);
    }

    public function assertConflict(): static
    {
        return $this->assertStatus(409);
    }

    public function assertGone(): static
    {
        return $this->assertStatus(410);
    }

    public function assertUnsupportedMediaType(): static
    {
        return $this->assertStatus(415);
    }

    public function assertUnprocessable(): static
    {
        return $this->assertStatus(422);
    }

    public function assertTooManyRequests(): static
    {
        return $this->assertStatus(429);
    }

    public function assertInternalServerError(): static
    {
        return $this->assertStatus(500);
    }

    public function assertServiceUnavailable(): static
    {
        return $this->assertStatus(503);
    }
}
