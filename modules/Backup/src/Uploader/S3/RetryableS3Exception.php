<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\S3;

use BC\Modules\Backup\Exception\BackupException;

/**
 * A transient S3 failure (network error, 5xx, 429, request timeout) — S3Client retries
 * the request instead of giving up.
 */
class RetryableS3Exception extends BackupException {
}
