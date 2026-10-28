<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Widget\AWidget;

/**
 * Raw HTML block. Its content is authored in the admin editor and printed to
 * the page verbatim — escaping it would defeat the whole point of the block.
 * Only authenticated admins can write it.
 */
class Html extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/html.phtml';
    }

    protected function getHtml(): string {
        return trim(MixedValue::toString($this->context['html'] ?? ''));
    }
}
