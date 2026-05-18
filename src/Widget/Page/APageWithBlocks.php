<?php

namespace BC\Widget\Page;

abstract class APageWithBlocks extends APage {
    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'blocks';

        return $list;
    }

    public function getCriticalJsBundles(): array {
        $list = parent::getCriticalJsBundles();

        $list[] = [
            'src'  => 'blocks',
            'type' => 'module'
        ];

        return $list;
    }
}
