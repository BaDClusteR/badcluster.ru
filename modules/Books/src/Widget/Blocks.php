<?php

namespace BC\Modules\Books\Widget;

use BC\Modules\Books\Widget\Common\Block\Paragraph;

class Blocks extends \BC\Widget\Blocks {
    protected function renderBlock(string $type, array $data): string {
        return ($type === 'paragraph')
            ? new Paragraph($data)->render()
            : parent::renderBlock($type, $data);
    }
}
