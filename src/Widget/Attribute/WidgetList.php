<?php

namespace BC\Widget\Attribute;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
readonly class WidgetList
{
    public function __construct(
        public string $name,
        public int $priority = 100,
    ) {
    }
}
