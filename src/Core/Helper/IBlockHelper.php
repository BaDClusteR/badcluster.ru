<?php

namespace BC\Core\Helper;

use Runway\Exception\Exception;

interface IBlockHelper {
    public function cleanBlocks(array $content): array;

    /**
     * @throws Exception
     */
    public function enrichBlocks(array $content): array;
}
