<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Txt\Block;

use BC\Widget\AWidget;

class Delimiter extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/txt/block/delimiter.phtml';
    }
}
