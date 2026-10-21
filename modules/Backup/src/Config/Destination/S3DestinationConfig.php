<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config\Destination;

readonly class S3DestinationConfig extends DestinationConfig {
    public function __construct(
        string $name,
        /** S3 API endpoint, e.g. 'https://<account>.r2.cloudflarestorage.com'. */
        public string $endpoint,
        public string $bucket,
        public string $accessKeyId,
        public string $secretAccessKey,
        /** Signing region; Cloudflare R2 expects 'auto'. */
        public string $region = 'auto',
        /** Key prefix the period "folders" are created under, e.g. 'bc'; empty → bucket root. */
        public string $prefix = '',
        /**
         * Multipart part size in MiB. Each part is retried on its own, so a dropped connection
         * costs one part instead of the whole archive. S3 requires at least 5 MiB.
         */
        public int $partSizeMb = 32,
        /**
         * Rotation: how many newest archives to keep per period folder; 0 = keep all.
         * Same semantics as for Google Drive — the uploader deletes stale archives itself
         * right after a successful upload.
         */
        public int $keepDaily = 3,
        public int $keepWeekly = 1,
        public int $keepMonthly = 1,
        public int $keepYearly = 0,
    ) {
        parent::__construct($name);
    }

    /** Keep-count for the given period folder name ('daily', 'weekly', …); 0 = keep all. */
    public function getKeepCount(string $periodFolder): int {
        return match ($periodFolder) {
            'daily'   => $this->keepDaily,
            'weekly'  => $this->keepWeekly,
            'monthly' => $this->keepMonthly,
            'yearly'  => $this->keepYearly,
            default   => 0,
        };
    }
}
