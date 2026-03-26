<?php

namespace BC\Widget\Head;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\DTO\MetaTagDTO;

#[WidgetList('head')]
class Meta extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'head/meta.phtml';
    }

    /**
     * @return MetaTagDTO[]
     */
    protected function getMeta(): array {
        return [
            new MetaTagDTO(
                name: 'viewport',
                content: 'width=device-width, initial-scale=1.0'
            )
        ];
    }
}