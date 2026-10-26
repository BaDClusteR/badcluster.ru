<?php

declare(strict_types=1);

namespace BC\Core\Helper;

interface IAttributesHelper {
    /**
     * @param array<string, string|null> $attributes Name => value, null for an attribute without a value.
     */
    public function getAttributesAsString(array $attributes): string;
}
