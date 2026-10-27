<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Modules\Backup\Config\Destination\DestinationConfig;
use BC\Modules\Backup\Uploader\DTO\UploadResult;
use BC\Modules\Backup\Uploader\DTO\UploadTarget;
use BC\Modules\Backup\Uploader\IBackupUploader;
use Throwable;

/**
 * Returns the given result per period (or throws the given exception); success by default.
 */
final class FakeUploader implements IBackupUploader {
    /** @var list<UploadTarget> */
    public array $targets = [];

    /** @var list<bool> Whether the archive existed at the time of each upload. */
    public array $archiveExisted = [];

    /**
     * @param array<string, UploadResult|Throwable> $results Period => result.
     */
    public function __construct(
        private readonly array $results = []
    ) {}

    public function supports(DestinationConfig $destination): bool {
        return true;
    }

    public function upload(DestinationConfig $destination, UploadTarget $target): UploadResult {
        $this->targets[] = $target;
        $this->archiveExisted[] = is_file($target->localFile);

        $result = $this->results[$target->periodFolder] ?? UploadResult::ok();

        if ($result instanceof Throwable) {
            throw $result;
        }

        return $result;
    }
}
