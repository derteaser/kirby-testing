<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Asserts\AssertDatalist;
use Derteaser\KirbyTesting\Dom\Asserts\AssertForm;
use Derteaser\KirbyTesting\Dom\Asserts\AssertSelect;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\DomCrawler\Crawler;

function formScope(): AssertForm
{
    $html = file_get_contents(__DIR__ . '/../Fixtures/html/form.html');

    if ($html === false) {
        throw new RuntimeException('Missing fixture: form.html');
    }

    return new AssertForm((new Crawler($html))->filter('form')->first());
}

it('normalizes action comparisons (leading / trailing slash, case)', function () {
    formScope()->hasAction('/contact');
    formScope()->hasAction('contact/');
    formScope()->hasAction('/CONTACT');
});

it('routes non-GET/POST methods to spoofed _method input', function () {
    formScope()->hasMethod('PATCH');
});

it('asserts real GET/POST methods directly on the form attribute', function () {
    formScope()->hasMethod('POST');
});

it('asserts CSRF presence via _token hidden input', function () {
    formScope()->hasCSRF();
});

it('fails when no spoof method matches', function () {
    formScope()->hasSpoofMethod('DELETE');
})->throws(AssertionFailedError::class, 'No spoof method for DELETE');

it('finds a select via findSelect and drives AssertSelect', function () {
    formScope()->findSelect(function (AssertSelect $select): void {
        $select->containsOptions(
            ['value' => 'apple', 'selected' => 'selected'],
            ['value' => 'banana'],
        )->containsOptgroup(['label' => 'Fruit']);
    });
});

it('asserts the selected option value via hasValue', function () {
    formScope()->findSelect(function (AssertSelect $select): void {
        $select->hasValue('apple');
    });
});

it('rejects non-id selectors for datalist', function () {
    formScope()->findDatalist('datalist.bogus', fn () => null);
})->throws(AssertionFailedError::class, 'Selectors for datalists must be an id');

it('finds a datalist by id and drives AssertDatalist', function () {
    formScope()->findDatalist('#colors', function (AssertDatalist $datalist): void {
        $datalist->containsOptions(
            ['value' => 'red'],
            ['value' => 'green'],
            ['value' => 'blue'],
        );
    });
});
