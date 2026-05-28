<?php

namespace BC\Modules\Games\Widget\GameMaterial;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\FormatterTrait;
use BC\Modules\Games\Model\GameMaterialFile;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use DateTime;

class File extends AWidget implements IAssetProvider {
    use FormatterTrait;
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Games/material/file.phtml';
    }

    protected function getFile(): ?GameMaterialFile {
        return (($this->context['file'] ?? null) instanceof GameMaterialFile)
            ? $this->context['file']
            : null;
    }

    protected function getDateAdded(): ?DateTime {
        return (($this->context['dateAdded'] ?? null) instanceof DateTime)
            ? $this->context['dateAdded']
            : null;
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'file',
                'css/modules/Games/file.css'
            ),
        ];
    }
}
