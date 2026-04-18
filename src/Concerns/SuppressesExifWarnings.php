<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Concerns;

/**
 * Swallows `exif_read_data: File not supported` warnings that Kirby emits when
 * probing non-JPEG image fixtures. Kirby prefixes the call with `@`, but
 * PHPUnit installs its own error handler that converts warnings into
 * exceptions and ignores `@` suppression.
 *
 * Usage in `tests/Pest.php`:
 *
 *     \Derteaser\KirbyTesting\Concerns\SuppressesExifWarnings::register();
 *
 * Idempotent — safe to call more than once, though register() is typically
 * invoked once per Pest bootstrap.
 */
final class SuppressesExifWarnings
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // set_error_handler() replaces the active handler — there is no stack.
        // Capture the previous one so we can delegate unrelated warnings.
        $previous = null;

        $previous = set_error_handler(
            function (int $errno, string $errstr, string $errfile = '', int $errline = 0) use (&$previous): bool {
                if (str_contains($errstr, 'exif_read_data') && str_contains($errstr, 'File not supported')) {
                    return true;
                }

                if (is_callable($previous)) {
                    return (bool) $previous($errno, $errstr, $errfile, $errline);
                }

                return false;
            },
            E_WARNING,
        );
    }
}
