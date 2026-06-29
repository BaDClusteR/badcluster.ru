<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget\AlbumsList;

use BC\Core\Trait\DateConverterTrait;
use BC\Modules\Music\Model\Album as AlbumModel;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Album extends AWidget {
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Music/albums/item.phtml';
    }

    protected function getAlbum(): ?AlbumModel {
        $result = $this->context['album'] ?? null;

        return ($result instanceof AlbumModel)
            ? $result
            : null;
    }

    protected function hasExplicitLanguage(): bool {
        try {
            return $this->getAlbum()->hasExplicitLanguage();
        } catch (Exception) {
            return false;
        }
    }
}
