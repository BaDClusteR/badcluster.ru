<?php

namespace BC\Core\Helper;

interface IAttributesHelper
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function getAttributesAsString(array $attributes): string;
}
