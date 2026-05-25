<?php

declare(strict_types=1);

namespace BC\Core\Helper;

interface IAttributesHelper {
    /**
     * @param array<string, mixed> $attributes
     */
    public function getAttributesAsString(array $attributes): string;
}
