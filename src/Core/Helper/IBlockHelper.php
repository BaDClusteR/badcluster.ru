<?php

declare(strict_types=1);

namespace BC\Core\Helper;

use Runway\Exception\Exception;

interface IBlockHelper {
    /**
     * @param array<string, mixed> $content The content of the editor: ['blocks' => [...], ...].
     *
     * @return array<string, mixed>
     */
    public function cleanBlocks(array $content): array;

    /**
     * @param array<string, mixed> $content The content of the editor: ['blocks' => [...], ...].
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function enrichBlocks(array $content): array;
}
