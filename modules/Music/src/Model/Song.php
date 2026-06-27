<?php

declare(strict_types=1);

namespace BC\Modules\Music\Model;

use BC\Model\AFile;
use Runway\DataStorage\Attribute as DS;

/**
 * @generated-model-helpers
 * @method int getId
 * @method self setId(int $id)
 * @method string getMime
 * @method self setMime(string $mime)
 * @method int getSize
 * @method self setSize(int $size)
 * @method string getPath
 * @method self setPath(string $path)
 * @method string getHash
 * @method self setHash(string $hash)
 * @method int getDuration()
 * @method self setDuration(int $duration)
 */
#[DS\Table('songs')]
class Song extends AFile {
    #[DS\Column]
    protected int $duration = 0;

    protected static function getSubfolder(): string {
        return 'music';
    }
}
