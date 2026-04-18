<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\TestResponse;
use Kirby\Cms\Response;
use PHPUnit\Framework\AssertionFailedError;

function makeResponse(?string $contentDisposition = null, int $status = 200, string $body = ''): TestResponse
{
    $headers = $contentDisposition !== null ? ['content-disposition' => $contentDisposition] : [];

    return new TestResponse(new Response($body, 'application/octet-stream', $status, $headers));
}

it('asserts status codes via assertStatus/assertOk', function () {
    makeResponse(null, 200, 'hi')->assertOk();
    makeResponse(null, 404)->assertStatus(404);
});

it('fails when status does not match', function () {
    makeResponse(null, 500)->assertOk();
})->throws(AssertionFailedError::class, 'Expected response status code [200]');

it('asserts body content with assertSee / assertDontSee', function () {
    makeResponse(null, 200, '<p>hello world</p>')
        ->assertSeeHtml('<p>hello world</p>')
        ->assertDontSeeHtml('<script>')
        ->assertSeeText('hello world');
});

describe('assertDownload', function () {
    it('passes when attachment is asserted without filename', function () {
        makeResponse('attachment')->assertDownload();
    });

    it('passes when attachment filename matches', function () {
        makeResponse('attachment; filename="report.pdf"')->assertDownload('report.pdf');
    });

    it('fails when content-disposition is missing', function () {
        makeResponse(null)->assertDownload();
    })->throws(AssertionFailedError::class, 'does not offer a file download');

    it('fails when disposition is inline, not attachment', function () {
        makeResponse('inline; filename="report.pdf"')->assertDownload('report.pdf');
    })->throws(AssertionFailedError::class, 'does not offer a file download');

    it('fails when filename is expected but header lacks one', function () {
        makeResponse('attachment')->assertDownload('report.pdf');
    })->throws(AssertionFailedError::class, 'is not present in Content-Disposition header');

    it('fails when second disposition token is not "filename"', function () {
        makeResponse('attachment; size=1024')->assertDownload('report.pdf');
    })->throws(AssertionFailedError::class, 'Unsupported Content-Disposition header');

    it('fails when filename does not match', function () {
        makeResponse('attachment; filename="other.pdf"')->assertDownload('report.pdf');
    })->throws(AssertionFailedError::class, 'Expected file [report.pdf] is not present in Content-Disposition header');
});
