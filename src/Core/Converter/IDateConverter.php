<?php

namespace BC\Core\Converter;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;

interface IDateConverter {
    public function toDateTime(string|int|DateTime $date): DateTime;

    public function toTimestamp(string|int|DateTime $date): int;

    public function toIsoFormat(string|int|DateTime $date): string;

    public function toShortForm(string|int|DateTime $date): string;

    public function toFullForm(string|int|DateTime $date, bool $includeTime): string;

    public function toPickerValue(string|int|DateTime $date): string;

    public function toRelativeForm(string|int|DateTime $date): string;
}
