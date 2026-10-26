<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Converter;

use BC\Core\Converter\DateConverter;
use BC\Tests\Fixture\Converter\LoggingDateConverter;
use BC\Tests\Support\LoggerSpy;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateConverter::class)]
final class DateConverterTest extends TestCase {
    /** 2024-03-05 08:07:09 UTC, 12:07:09 in Samara (UTC+4). */
    private const int TIMESTAMP = 1709626029;

    private DateConverter $converter;

    protected function setUp(): void {
        $this->converter = new DateConverter('Europe/Samara');
    }

    public function testToDateTimeFromTimestamp(): void {
        $date = $this->converter->toDateTime(self::TIMESTAMP);

        self::assertSame(self::TIMESTAMP, $date->getTimestamp());
        self::assertSame('Europe/Samara', $date->getTimezone()->getName());
        self::assertSame('2024-03-05 12:07:09', $date->format('Y-m-d H:i:s'));
    }

    public function testToDateTimeFromString(): void {
        $date = $this->converter->toDateTime('2024-03-05T08:07:09+00:00');

        self::assertSame(self::TIMESTAMP, $date->getTimestamp());
        self::assertSame('Europe/Samara', $date->getTimezone()->getName());
    }

    public function testToDateTimeFromDateTime(): void {
        $source = new DateTime('@' . self::TIMESTAMP);
        $date = $this->converter->toDateTime($source);

        self::assertNotSame($source, $date);
        self::assertSame(self::TIMESTAMP, $date->getTimestamp());
        self::assertSame('Europe/Samara', $date->getTimezone()->getName());
        self::assertSame('+00:00', $source->getTimezone()->getName(), 'The source date is not changed.');
    }

    public function testInvalidStringIsTheEpoch(): void {
        $converter = new LoggingDateConverter('Europe/Samara', new LoggerSpy());
        $date = $converter->toDateTime('not a date at all');

        self::assertSame(0, $date->getTimestamp());

        $records = $converter->logger->getRecords('warning');
        self::assertCount(1, $records);
        self::assertStringStartsWith(
            DateConverter::class . '::convertToDateTime. Date malformed: Failed to parse time string (@)',
            $records[0]['message']
        );
        self::assertSame([], $records[0]['context']);
    }

    public function testInvalidTimezoneGivesTheEpoch(): void {
        $converter = new LoggingDateConverter('Mars/Olympus', new LoggerSpy());
        $date = $converter->toDateTime(self::TIMESTAMP);

        self::assertSame(0, $date->getTimestamp());
        self::assertSame(
            [
                [
                    'level'   => 'warning',
                    'message' => DateConverter::class . '::convertToDateTime. Invalid timezone:'
                        . ' DateTimeZone::__construct(): Unknown or bad timezone (Mars/Olympus)',
                    'context' => ['date' => self::TIMESTAMP, 'timestamp' => self::TIMESTAMP, 'timezone' => 'Mars/Olympus'],
                ],
            ],
            $converter->logger->records
        );
    }

    public function testValidDateIsNotLogged(): void {
        $converter = new LoggingDateConverter('Europe/Samara', new LoggerSpy());
        $converter->toDateTime('2024-01-01');

        self::assertSame([], $converter->logger->records);
    }

    public function testYearBeforeTheThousand(): void {
        $date = new DateTime('0999-05-07 12:00:00', new DateTimeZone('Europe/Samara'));

        self::assertSame('7 мая 999', $this->converter->toShortForm($date));
        self::assertSame('7 мая 999г.', $this->converter->toFullForm($date, false));
    }

    public function testToTimestamp(): void {
        self::assertSame(self::TIMESTAMP, $this->converter->toTimestamp(self::TIMESTAMP));
        self::assertSame(-5, $this->converter->toTimestamp(-5));
        self::assertSame(self::TIMESTAMP, $this->converter->toTimestamp('2024-03-05 12:07:09 Europe/Samara'));
        self::assertSame(self::TIMESTAMP, $this->converter->toTimestamp(new DateTime('@' . self::TIMESTAMP)));
    }

    public function testToIsoFormat(): void {
        self::assertSame('2024-03-05T12:07:09+04:00', $this->converter->toIsoFormat(self::TIMESTAMP));
    }

    public function testToPickerValue(): void {
        self::assertSame('2024-03-05T12:07:09+04:00', $this->converter->toPickerValue(self::TIMESTAMP));
    }

    /**
     * @return iterable<array{string, string, string}> Local date => the short form, the full form.
     */
    public static function provideMonths(): iterable {
        yield ['2024-01-05', '5 янв 2024', '5 января 2024г.'];
        yield ['2024-02-05', '5 фев 2024', '5 февраля 2024г.'];
        yield ['2024-03-05', '5 мар 2024', '5 марта 2024г.'];
        yield ['2024-04-05', '5 апр 2024', '5 апреля 2024г.'];
        yield ['2024-05-05', '5 мая 2024', '5 мая 2024г.'];
        yield ['2024-06-05', '5 июн 2024', '5 июня 2024г.'];
        yield ['2024-07-05', '5 июл 2024', '5 июля 2024г.'];
        yield ['2024-08-05', '5 авг 2024', '5 августа 2024г.'];
        yield ['2024-09-05', '5 сен 2024', '5 сентября 2024г.'];
        yield ['2024-10-05', '5 окт 2024', '5 октября 2024г.'];
        yield ['2024-11-05', '5 ноя 2024', '5 ноября 2024г.'];
        yield ['2024-12-05', '5 дек 2024', '5 декабря 2024г.'];
        yield ['2024-12-10', '10 дек 2024', '10 декабря 2024г.'];
        yield ['2024-12-31', '31 дек 2024', '31 декабря 2024г.'];
    }

    #[DataProvider('provideMonths')]
    public function testShortAndFullForms(string $localDate, string $shortForm, string $fullForm): void {
        $date = new DateTime("$localDate 23:30:00", new DateTimeZone('Europe/Samara'));

        self::assertSame($shortForm, $this->converter->toShortForm($date));
        self::assertSame($fullForm, $this->converter->toFullForm($date, false));
    }

    public function testFullFormWithTime(): void {
        self::assertSame('5 марта 2024г. в 12:07', $this->converter->toFullForm(self::TIMESTAMP, true));
        self::assertSame('5 марта 2024г. в 08:07', new DateConverter('UTC')->toFullForm(self::TIMESTAMP, true));
    }

    public function testFormsUseTheTimezone(): void {
        // 2024-03-05 22:00 UTC is already March 6 in Samara.
        $timestamp = (int)strtotime('2024-03-05 22:00:00 UTC');

        self::assertSame('6 мар 2024', $this->converter->toShortForm($timestamp));
        self::assertSame('5 мар 2024', new DateConverter('UTC')->toShortForm($timestamp));
    }

    /**
     * @return iterable<array{string, string}> The relative date (see strtotime()) => the expected text.
     */
    public static function provideRelativeDates(): iterable {
        yield ['+1 hour', 'В будущем'];
        yield ['-10 seconds', 'Только что'];
        yield ['-59 seconds', 'Только что'];
        yield ['-1 minute -5 seconds', 'минуту назад'];
        yield ['-2 minutes', '2 минуты назад'];
        yield ['-4 minutes', '4 минуты назад'];
        yield ['-5 minutes', '5 минут назад'];
        yield ['-11 minutes', '11 минут назад'];
        yield ['-12 minutes', '12 минут назад'];
        yield ['-14 minutes', '14 минут назад'];
        yield ['-20 minutes', '20 минут назад'];
        yield ['-21 minutes', '21 минуту назад'];
        yield ['-22 minutes', '22 минуты назад'];
        yield ['-59 minutes', '59 минут назад'];
        yield ['-1 hour', 'час назад'];
        yield ['-2 hours', '2 часа назад'];
        yield ['-3 hours', '3 часа назад'];
        yield ['-5 hours', '5 часов назад'];
        yield ['-11 hours', '11 часов назад'];
        yield ['-21 hours', '21 час назад'];
        yield ['-23 hours', '23 часа назад'];
        yield ['-1 day -1 minute', 'вчера'];
        yield ['-2 days', 'позавчера'];
        yield ['-3 days', '3 дня назад'];
        yield ['-5 days', '5 дней назад'];
        yield ['-11 days', '11 дней назад'];
        yield ['-21 days', '21 день назад'];
        yield ['-1 month -1 day', 'месяц назад'];
        yield ['-2 months -1 day', '2 месяца назад'];
        yield ['-5 months -1 day', '5 месяцев назад'];
        yield ['-11 months -1 day', '11 месяцев назад'];
        yield ['-1 year -1 day', 'год назад'];
        yield ['-2 years -1 day', '2 года назад'];
        yield ['-5 years -1 day', '5 лет назад'];
        yield ['-11 years -1 day', '11 лет назад'];
        yield ['-12 years -1 day', '12 лет назад'];
        yield ['-14 years -1 day', '14 лет назад'];
        yield ['-21 years -1 day', '21 год назад'];
        yield ['-22 years -1 day', '22 года назад'];
        yield ['-111 years -1 day', '111 лет назад'];
    }

    #[DataProvider('provideRelativeDates')]
    public function testRelativeForm(string $relativeDate, string $expected): void {
        $timestamp = (int)strtotime($relativeDate);

        self::assertSame($expected, $this->converter->toRelativeForm($timestamp));
    }

    public function testRelativeFormOfNow(): void {
        self::assertSame('Только что', $this->converter->toRelativeForm(time()));
        self::assertSame('Только что', $this->converter->toRelativeForm('now'));
    }
}
