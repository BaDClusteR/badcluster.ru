<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config\Destination;

readonly class GoogleDriveDestinationConfig extends DestinationConfig {
    public function __construct(
        string $name,
        /** Absolute path to the service-account key JSON file (kept outside the repo). */
        public string $keyFilePath,
        /** ID of the Drive folder the period sub-folders are created under. */
        public string $folderId,
        /**
         * Rotation: how many newest archives to keep per period folder; 0 = keep all.
         * Unlike SFTP (where deletion is forbidden server-side), Drive is a convenience
         * mirror — the uploader rotates old backups itself right after a successful upload.
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
