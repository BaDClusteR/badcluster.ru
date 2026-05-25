<?php

declare(strict_types=1);

namespace BC\Core\Media\Processor\Command;

use BC\Core\Exception\ImageException;

interface ICommand {
    /**
     * @throws ImageException
     */
    public function execute(): void;
}
