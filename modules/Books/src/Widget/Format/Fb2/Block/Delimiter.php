<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2\Block;

use BC\Widget\AWidget;

class Delimiter extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/block/delimiter.phtml';
    }
}
