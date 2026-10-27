<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

/**
 * Runs code that triggers an expected PHP warning (e.g. fopen() of a missing file) as if it were called with "@":
 * PHPUnit records the issue as suppressed, so it does not fail the strict run.
 */
trait Quietly {
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    protected static function quietly(callable $callback): mixed {
        $level = error_reporting(0);

        try {
            return $callback();
        } finally {
            error_reporting($level);
        }
    }
}
