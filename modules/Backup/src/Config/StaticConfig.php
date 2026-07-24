<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config;

/**
 * Which sub-folders of /static end up in the archive.
 *
 * Folders are named relative to $root (e.g. 'photos', 'music'), not as absolute paths.
 */
readonly class StaticConfig {
    /**
     * @param string   $root           absolute path to the /static directory
     * @param bool     $greedy         true: archive EVERY sub-folder EXCEPT $excludeFolders.
     *                                 false: archive ONLY $includeFolders.
     * @param string[] $includeFolders sub-folder names, used when $greedy is false
     * @param string[] $excludeFolders sub-folder names, used when $greedy is true
     */
    public function __construct(
        public string $root,
        public bool $greedy = false,
        public array $includeFolders = ['cringe', 'games', 'media', 'music', 'photos', 'screenshots'],
        public array $excludeFolders = [],
    ) {}
}