<?php

declare(strict_types=1);

namespace BC\Core\Formatter;

class Formatter implements IFormatter {
    protected const array SIZE_POSTFIXES = ['байт', 'Кб', 'Мб', 'Гб', 'Тб', 'Пб', 'Эб'];

    public function formatAsHumanReadableSize(int $sizeInBytes): string {
        $size = (float) $sizeInBytes;

        foreach (static::SIZE_POSTFIXES as $i => $postfix) {
            if ($size < 1024 || $i === count(static::SIZE_POSTFIXES) - 1) {
                return sprintf('%.2f %s', $size, $postfix);
            }

            $size /= 1024;
        }

        return '';
    }
}
