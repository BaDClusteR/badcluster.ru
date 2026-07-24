<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\DTO;

readonly class UploadResult {
    public function __construct(
        public bool $success,
        /** Public download URL, when the destination can produce one. */
        public ?string $url = null,
        /**
         * When $success is false — the failure reason.
         * When $success is true — an optional non-fatal warning (e.g. upload went through
         * but post-upload rotation failed); recorded in the backup log without failing the run.
         */
        public ?string $error = null,
    ) {}

    public static function ok(?string $url = null, ?string $warning = null): self {
        return new self(true, $url, $warning);
    }

    public static function fail(string $error): self {
        return new self(false, null, $error);
    }
}
