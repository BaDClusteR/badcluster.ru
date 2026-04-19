<?php

namespace BC\Core\Converter;

use BC\Core\Converter\IConverter;
use BC\Model\Config;
use DateTime;
use Runway\Singleton;

class ConverterFactory extends Singleton implements IConverter
{
    private Converter $converter;

    public function __construct()
    {
        $this->converter = new Converter(
            dateFormat: Config::getConfig('date_format'),
            dateTimeFormat: Config::getConfig('datetime_format'),
            timezone: Config::getConfig('timezone')
        );
    }

    public function convertTimestampToDate(int $timestamp): DateTime
    {
        return $this->converter->convertTimestampToDate($timestamp);
    }

    public function convertTimestampToDateTime(int $timestamp): DateTime
    {
        return $this->converter->convertTimestampToDateTime($timestamp);
    }

    public function convertDateTimeToTimestamp(DateTime $dateTime): int
    {
        return $this->converter->convertDateTimeToTimestamp($dateTime);
    }

    public function convertTimestampToDateString(int $timestamp): string
    {
        return $this->converter->convertTimestampToDateString($timestamp);
    }

    public function convertTimestampToTimeString(int $timestamp): string
    {
        return $this->converter->convertTimestampToTimeString($timestamp);
    }

    public function convertTimestampToDateTimeString(int $timestamp): string
    {
        return $this->converter->convertTimestampToDateTimeString($timestamp);
    }
}
