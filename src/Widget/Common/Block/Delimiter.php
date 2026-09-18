<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class Delimiter extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/delimiter.phtml';
    }
}
