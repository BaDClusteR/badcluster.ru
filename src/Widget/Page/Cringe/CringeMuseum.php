<?php

namespace BC\Widget\Page\Cringe;

use BC\Core\Trait\FormatterTrait;
use BC\Widget\AWidget;

class CringeMuseum extends AWidget {
    use FormatterTrait;

    protected function getTemplatePath(): string {
        return 'about/cringe_museum.phtml';
    }
}
