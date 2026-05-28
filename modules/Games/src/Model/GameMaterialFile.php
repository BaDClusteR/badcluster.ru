<?php

declare(strict_types=1);

namespace BC\Modules\Games\Model;

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
 */
#[DS\Table('game_material_files')]
class GameMaterialFile extends AFile {
    protected static function getSubfolder(): string {
        return 'games';
    }
}
