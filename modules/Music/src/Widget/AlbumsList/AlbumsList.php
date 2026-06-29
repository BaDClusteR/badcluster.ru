<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget\AlbumsList;

use BC\Modules\Music\Model\Album;
use BC\Widget\AWidget;

class AlbumsList extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Music/albums/list.phtml';
    }

    /**
     * @return Album[]
     */
    protected function getAlbums(): array {
        return (array) ($this->context['albums'] ?? []);
    }
}
