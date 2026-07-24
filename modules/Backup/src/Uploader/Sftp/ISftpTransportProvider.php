<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Uploader\Sftp;

interface ISftpTransportProvider {
    /**
     * Returns the first transport (by tag priority) available in this environment,
     * or null when none can run here.
     */
    public function getTransport(): ?ISftpTransport;

    /**
     * One line per registered transport: its name and whether/why not it is available.
     * Used to build actionable error messages when no transport works.
     *
     * @return string[]
     */
    public function getDiagnostics(): array;
}
