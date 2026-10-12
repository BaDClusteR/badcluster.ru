<?php

namespace BC\Modules\Books\Widget\Common\Block;

use BC\Modules\Books\Core\Typography\ITypographer;
use Runway\Singleton\Container;

class Paragraph extends \BC\Widget\Common\Block\Paragraph {
    protected function getTemplatePath(): string {
        return 'modules/Books/common/block/paragraph.phtml';
    }

    protected function getContent(): string {
        return Container::getInstance()->getService(ITypographer::class)->process(
            parent::getContent()
        );
    }

    protected function isDirectSpeech(string $paragraph): bool {
        // После типографа за тире в начале реплики стоит неразрывный пробел.
        $paragraph = trim(
            str_replace(['&nbsp;', "\u{00A0}"], ' ', $paragraph)
        );

        return str_starts_with($paragraph, '— ')
               || str_starts_with($paragraph, '– ')
               || str_starts_with($paragraph, '- ');
    }
}
