<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Period;

use DateTimeInterface;

class PeriodResolver implements IPeriodResolver {
    public function resolve(DateTimeInterface $date): array {
        $folders = ['daily'];

        if ((int)$date->format('N') === 1) {
            $folders[] = 'weekly';
        }

        if ((int)$date->format('j') === 1) {
            $folders[] = 'monthly';
        }

        if ($date->format('n-j') === '1-1') {
            $folders[] = 'yearly';
        }

        return $folders;
    }
}