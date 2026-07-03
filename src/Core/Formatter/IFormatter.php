<?php

namespace BC\Core\Formatter;

interface IFormatter {
    public function formatAsHumanReadableSize(int $sizeInBytes): string;

    public function formatAsHtml(string $plainText): string;

    public function formatAsHumanReadableDuration(int $durationInSeconds): string;

    public function formatAsWordForm(int $count, string $firstForm, string $secondForm, string $thirdForm): string;
}
