<?php

declare(strict_types=1);

namespace BC\Core\Helper;

use Stringable;

/**
 * Приведение значений неизвестного типа (строки БД, JSON, контекст виджетов, переменные окружения): скаляр
 * приводится как обычным (int) / (string) / (float), всё остальное (null, массив, объект) даёт значение по умолчанию —
 * без "Array" и предупреждений.
 */
final class MixedValue {
    public static function toInt(mixed $value, int $default = 0): int {
        return is_scalar($value)
            ? (int) $value
            : $default;
    }

    public static function toFloat(mixed $value, float $default = 0.0): float {
        return is_scalar($value)
            ? (float) $value
            : $default;
    }

    public static function toString(mixed $value, string $default = ''): string {
        return (is_scalar($value) || $value instanceof Stringable)
            ? (string) $value
            : $default;
    }
}
