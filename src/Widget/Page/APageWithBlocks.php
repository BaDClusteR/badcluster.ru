<?php

namespace BC\Widget\Page;

abstract class APageWithBlocks extends APage
{
    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = "blocks";

        return $list;
    }

    public function getJsBundles(): array
    {
        $list = parent::getJsBundles();

        $list[] = [
            'src'  => "blocks",
            'type' => "module"
        ];

        return $list;
    }
}
