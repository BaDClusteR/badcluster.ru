<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config\Destination;

readonly class FtpDestinationConfig extends DestinationConfig {
    public function __construct(
        string $name,
        public string $host,
        public int $port,
        public string $username,
        public string $password,
        /** Remote base directory the period sub-folders are created under, e.g. '/backups'. */
        public string $basePath = '/',
        public bool $passive = true,
        /** Use FTPS (explicit TLS) instead of plain FTP. */
        public bool $ssl = false,
        /**
         * Optional public HTTP base that maps to $basePath, used to build a download URL
         * stored in the backup log (e.g. 'https://files.example.com/backups'). Null → no URL.
         */
        public ?string $publicUrlBase = null,
    ) {
        parent::__construct($name);
    }
}