<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config\Destination;

readonly class SftpDestinationConfig extends DestinationConfig {
    public function __construct(
        string $name,
        public string $host,
        public int $port,
        public string $username,
        /** Password for password auth; may be empty when key auth is used. */
        public string $password = '',
        /** Absolute path to an SSH private key file for key auth; empty → password auth only. */
        public string $keyFilePath = '',
        /** Passphrase of the private key, when it has one. */
        public string $keyPassphrase = '',
        /**
         * Remote base directory the period sub-folders are created under.
         * A leading '/' means an absolute path; use '~/backups' for a home-relative one.
         */
        public string $basePath = '~',
        /**
         * Optional SHA256 pin of the server's host key (base64, the part after "SHA256:" in
         * `ssh-keyscan host | ssh-keygen -lf -` output). Null → no host key verification.
         */
        public ?string $hostKeySha256 = null,
        /**
         * Optional public HTTP base that maps to $basePath, used to build a download URL
         * stored in the backup log. Null → no URL.
         */
        public ?string $publicUrlBase = null,
    ) {
        parent::__construct($name);
    }
}
