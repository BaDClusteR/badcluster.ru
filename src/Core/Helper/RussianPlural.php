<?php

declare(strict_types=1);

namespace BC\Core\Helper;

final class RussianPlural {
    /**
     * Выбирает форму слова по числу: form(1, 'ошибка', 'ошибки', 'ошибок') → 'ошибка',
     * form(3, ...) → 'ошибки', form(11, ...) → 'ошибок'.
     */
    public static function form(int $count, string $one, string $few, string $many): string {
        $mod100 = abs($count) % 100;
        $mod10 = $mod100 % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $many;
        }

        if ($mod10 === 1) {
            return $one;
        }

        return ($mod10 >= 2 && $mod10 <= 4) ? $few : $many;
    }
}
