<?php

namespace BC\Core\Helper;

class AttributesHelper implements IAttributesHelper
{
    /**
     * @inheritDoc
     */
    public function getAttributesAsString(array $attributes): string
    {
        $pieces = [];
        foreach ($attributes as $key => $value) {
            $pieces[] = ($value === null)
                ? htmlspecialchars($key, ENT_QUOTES)
                : htmlspecialchars($key, ENT_QUOTES) . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
        }

        return implode(' ', $pieces);
    }
}
