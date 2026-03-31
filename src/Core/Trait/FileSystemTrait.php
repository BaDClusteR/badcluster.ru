<?php

declare(strict_types=1);

namespace BC\Core\Trait;

use Runway\FileSystem\IFileSystem;
use Runway\Singleton\Container;

trait FileSystemTrait
{
    protected function getFileSystem(): IFileSystem {
        return Container::getInstance()->getService(IFileSystem::class);
    }
}
