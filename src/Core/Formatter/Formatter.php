<?php

declare(strict_types=1);

namespace BC\Core\Formatter;

class Formatter implements IFormatter {
    protected const array SIZE_POSTFIXES = ['байт', 'Кб', 'Мб', 'Гб', 'Тб', 'Пб', 'Эб'];

    public function formatAsHumanReadableSize(int $sizeInBytes): string {
        $size = (float) $sizeInBytes;

        foreach (static::SIZE_POSTFIXES as $i => $postfix) {
            if ($size < 1024 || $i === count(static::SIZE_POSTFIXES) - 1) {
                $sizeStr = rtrim(number_format($size, 2), '0.');
                if (!$sizeStr) {
                    $sizeStr = '0';
                }

                return sprintf('%s %s', $sizeStr, $postfix);
            }

            $size /= 1024;
        }

        return '';
    }

    public function formatAsHtml(string $plainText): string {
        $plainText = str_replace(
            "\n\n",
            "\n",
            $plainText
        );

        return '<p>' . implode('</p><p>', explode("\n", $plainText)) . '</p>';
    }

    public function formatAsHumanReadableDuration(int $durationInSeconds): string {
        $hours = 0;
        $minutes = 0;

        if ($durationInSeconds > 3600) {
            $hours = (int) floor($durationInSeconds / 3600);
            $durationInSeconds -= ($hours * 3600);
        }

        if ($durationInSeconds > 60) {
            $minutes = (int) floor($durationInSeconds / 60);
            $durationInSeconds -= ($minutes * 60);
        }

        $seconds = $durationInSeconds;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%d:%02d', $minutes, $seconds);
    }
}
