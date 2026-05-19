<?php

namespace BC\Core\Converter;

use BC\Core\Trait\LoggerTrait;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeZone;

readonly class DateConverter implements IDateConverter {
    use LoggerTrait;

    public function __construct(
        private string $timezone
    ) {
    }

    public function toDateTime(DateTime|int|string $date): DateTime {
        return $this->convertToDateTime($date);
    }

    public function toTimestamp(DateTime|int|string $date): int {
        if (is_int($date)) {
            return $date;
        }

        return $this->convertToDateTime($date)->getTimestamp();
    }

    public function toIsoFormat(DateTime|int|string $date): string {
        return $this->convertToDateTime($date)->format(DATE_ATOM);
    }

    public function toShortForm(DateTime|int|string $date): string {
        $dt = $this->convertToDateTime($date);

        $day = ltrim($dt->format('d'), '0');
        $month = $this->getShortMonthName(
            (int) $dt->format('m')
        );
        $year = ltrim($dt->format('Y'), '0');

        return sprintf('%s %s %s', $day, $month, $year);
    }

    public function toFullForm(DateTime|int|string $date, bool $includeTime): string {
        $dt = $this->convertToDateTime($date);

        $day = ltrim($dt->format('d'), '0');
        $month = $this->getFullMonthName(
            (int) $dt->format('m')
        );
        $year = ltrim($dt->format('Y'), '0');

        $time = $includeTime
            ? sprintf(' в %s', $dt->format('H:i'))
            : '';

        return sprintf('%s %s %sг.%s', $day, $month, $year, $time);
    }

    public function toPickerValue(DateTime|int|string $date): string {
        return $this->convertToDateTime($date)->format('Y-m-d\TH:i:sP');
    }

    public function toRelativeForm(DateTime|int|string $date): string {
        $dt = $this->convertToDateTime($date);
        $now = $this->convertToDateTime('now');

        if ($dt->getTimestamp() > $now->getTimestamp()) {
            return 'В будущем';
        }

        $interval = $now->diff($dt);

        if ($interval->y > 0) {
            return $this->formatYearsInterval($interval->y);
        }

        if ($interval->m > 0) {
            return $this->formatMonthInterval($interval->m);
        }

        if ($interval->d > 0) {
            return $this->formatDayInterval($interval->d);
        }

        if ($interval->h > 0) {
            return $this->formatHourInterval($interval->h);
        }

        if ($interval->i > 0) {
            return $this->formatMinuteInterval($interval->i);
        }

        return 'Только что';
    }

    private function formatYearsInterval(int $years): string {
        return $this->formatInterval($years, '{{diff}} год назад', '{{diff}} года назад', '{{diff}} лет назад');
    }

    private function formatMonthInterval(int $months): string {
        return $this->formatInterval($months, '{{diff}} месяц назад', '{{diff}} месяца назад', '{{diff}} месяцев назад');
    }

    private function formatDayInterval(int $days): string {
        return match ($days) {
            1 => 'вчера',
            2 => 'позавчера',
            default => $this->formatInterval($days, '{{diff}} день назад', '{{diff}} дня назад', '{{diff}} дней назад')
        };
    }

    private function formatHourInterval(int $hours): string {
        return $this->formatInterval($hours, '{{diff}} час назад', '{{diff}} часов назад', '{{diff}} часов назад');
    }

    private function formatMinuteInterval(int $minutes): string {
        return $this->formatInterval($minutes, '{{diff}} минуту назад', '{{diff}} минут назад', '{{diff}} минут назад');
    }

    private function formatInterval(int $diff, string $firstForm, string $secondForm, string $thirdForm): string {
        $diffMod10 = abs($diff) % 10;

        if ($diffMod10 === 1) {
            $template = $firstForm;
        } elseif (in_array($diffMod10, [2, 3, 4])) {
            $template = $secondForm;
        } else {
            $template = $thirdForm;
        }

        if ($diff === 1) {
            $template = str_replace('{{diff}} ', '', $template);
        }

        return str_replace('{{diff}}', $diff, $template);
    }

    private function convertToDateTime(int|string|DateTime $date): DateTime {
        $timestamp = 0;
        if (is_int($date)) {
            $timestamp = $date;
        } elseif (is_string($date)) {
            $timestamp = strtotime($date);
        } elseif ($date instanceof DateTime) {
            $timestamp = $date->getTimestamp();
        }

        try {
            return new DateTime('@' . $timestamp)->setTimezone(
                new DateTimeZone($this->timezone)
            );
        } catch (DateInvalidTimeZoneException $e) {
            $this->getLogger()->warning(
                __METHOD__ . ". Invalid timezone: {$e->getMessage()}",
                [
                    'date'      => $date,
                    'timestamp' => $timestamp,
                    'timezone'  => $this->timezone,
                ]
            );

            return new DateTime('@0');
        } catch (DateMalformedStringException $e) {
            $this->getLogger()->warning(
                __METHOD__ . ". Date malformed: {$e->getMessage()}"
            );

            return new DateTime('@0');
        }
    }

    private function getShortMonthName(int $month): string {
        return match ($month) {
            1 => 'янв',
            2 => 'фев',
            3 => 'мар',
            4 => 'апр',
            5 => 'мая',
            6 => 'июн',
            7 => 'июл',
            8 => 'авг',
            9 => 'сен',
            10 => 'окт',
            11 => 'ноя',
            12 => 'дек',
            default => ''
        };
    }

    private function getFullMonthName(int $month): string {
        return match ($month) {
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
            default => ''
        };
    }
}
