<?php

namespace BC\Core\Formatter;

interface IFormatter {
    public function formatAsHumanReadableSize(int $sizeInBytes): string;

    public function formatAsHtml(string $plainText): string;
}
