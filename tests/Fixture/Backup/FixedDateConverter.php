<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Backup;

use BC\Core\Converter\IDateConverter;
use DateTime;
use LogicException;

/**
 * "Now" is the given moment: toDateTime() returns it whatever date it gets.
 */
final readonly class FixedDateConverter implements IDateConverter {
    public function __construct(
        private string $now
    ) {}

    public function toDateTime(DateTime|int|string $date): DateTime {
        return new DateTime($this->now);
    }

    public function toTimestamp(DateTime|int|string $date): int {
        throw new LogicException('Not expected in the test.');
    }

    public function toIsoFormat(DateTime|int|string $date): string {
        throw new LogicException('Not expected in the test.');
    }

    public function toShortForm(DateTime|int|string $date): string {
        throw new LogicException('Not expected in the test.');
    }

    public function toFullForm(DateTime|int|string $date, bool $includeTime): string {
        throw new LogicException('Not expected in the test.');
    }

    public function toPickerValue(DateTime|int|string $date): string {
        throw new LogicException('Not expected in the test.');
    }

    public function toRelativeForm(DateTime|int|string $date): string {
        throw new LogicException('Not expected in the test.');
    }
}
