<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Period;

use DateTimeInterface;

interface IPeriodResolver {
    /**
     * Returns the remote period sub-folders an archive for $date should be uploaded into.
     *
     * The site only ever WRITES backups (never deletes them remotely); retention is handled
     * on the storage side. A daily run always yields 'daily', plus 'weekly' on Mondays,
     * 'monthly' on the 1st, and 'yearly' on Jan 1.
     *
     * @return string[] e.g. ['daily'] or ['daily', 'weekly', 'monthly', 'yearly']
     */
    public function resolve(DateTimeInterface $date): array;
}