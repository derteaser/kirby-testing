<?php

declare(strict_types=1);

/*
 * Pest bootstrap for the package's own test suite. Deliberately self-contained:
 * no Kirby App is booted — unit tests drive DOM assertions against static HTML
 * and TestResponse against a directly-constructed Kirby\Cms\Response.
 */
