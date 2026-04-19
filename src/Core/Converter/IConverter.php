<?php

namespace BC\Core\Converter;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;

interface IConverter
{
    /**
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     */
    public function convertTimestampToDateTime(int $timestamp): DateTime;

    public function convertDateTimeToTimestamp(DateTime $dateTime): int;

    public function convertTimestampToDateString(int $timestamp): string;

    public function convertTimestampToTimeString(int $timestamp): string;

    public function convertTimestampToDateTimeString(int $timestamp): string;
}
