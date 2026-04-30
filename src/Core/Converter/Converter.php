<?php

namespace BC\Core\Converter;

use BC\Core\Trait\LoggerTrait;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeZone;

readonly class Converter implements IConverter
{
    use LoggerTrait;

    public function __construct(
        private string $dateFormat,
        private string $dateTimeFormat,
        private string $timezone
    ) {
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function convertTimestampToDateTime(int $timestamp): DateTime
    {
        return new DateTime('@' . $timestamp)->setTimezone(
            new DateTimeZone($this->timezone)
        );
    }

    public function convertDateTimeToTimestamp(DateTime $dateTime): int
    {
        return $dateTime->getTimestamp();
    }

    public function convertTimestampToDateString(int $timestamp): string
    {
        return $this->convertTimestampToString($timestamp, $this->dateFormat);
    }

    public function convertTimestampToTimeString(int $timestamp): string
    {
        return $this->convertTimestampToString($timestamp, "H:i");
    }

    public function convertTimestampToDateTimeString(int $timestamp): string
    {
        return $this->convertTimestampToString($timestamp, $this->dateTimeFormat);
    }

    private function convertTimestampToString(int $timestamp, string $format): string {
        try {
            return $this->convertTimestampToDateTime($timestamp)->format($format);
        } catch (DateInvalidTimeZoneException $e) {
            $this->getLogger()->warning(
                __METHOD__ . ". Invalid timezone: {$e->getMessage()}"
            );

            return '';
        } catch (DateMalformedStringException $e) {
            $this->getLogger()->warning(
                __METHOD__ . ". Date malformed: {$e->getMessage()}"
            );

            return '';
        }
    }

    public function convertTimestampToHumanReadableDate(int $timestamp): string
    {
        $day = ltrim(date('d', $timestamp), '0');
        $month = $this->getMonthName(
            (int)date('m', $timestamp)
        );
        $year = ltrim(date('Y', $timestamp), '0');

        return sprintf("%s %s %s", $day, $month, $year);
    }

    private function getMonthName(int $month): string {
        return match ($month) {
            1 => "янв",
            2 => "фев",
            3 => "мар",
            4 => "апр",
            5 => "мая",
            6 => "июн",
            7 => "июл",
            8 => "авг",
            9 => "сен",
            10 => "окт",
            11 => "ноя",
            12 => "дек",
            default => ""
        };
    }
}
