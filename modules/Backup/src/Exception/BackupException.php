<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Exception;

use RuntimeException;

/**
 * Thrown when a backup cannot be produced (dump, archiving or a fatal orchestration error).
 * Upload failures are reported per-destination via UploadResult rather than thrown.
 */
class BackupException extends RuntimeException {
}