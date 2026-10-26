<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Logger;

use BC\Core\Log\LogRecorder;

/**
 * LogRecorder on the SQLite table of the test (see SqliteSchema): the table of ensureSchema() is MySQL-only.
 */
final class SqliteLogRecorder extends LogRecorder {
    public int $ensureSchemaCalls = 0;

    public function ensureSchema(): void {
        $this->ensureSchemaCalls++;
    }
}
