<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget;

use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\FormatterTrait;
use BC\Modules\Music\Model\Album;
use BC\Widget\AWidget;

class Release extends AWidget {
    use FormatterTrait;
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Music/release.phtml';
    }

    protected function getAlbum(): ?Album {
        return (($this->context['album'] ?? null) instanceof Album)
            ? $this->context['album']
            : null;
    }
}
