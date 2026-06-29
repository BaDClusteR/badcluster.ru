<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget;

use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\FormatterTrait;
use BC\Modules\Music\Model\Album;
use BC\Modules\Music\Model\Album as AlbumModel;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

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

    protected function getGenres(): array {
        return array_map(
            static fn (string $genre): string => trim($genre),
            explode(',', $this->getAlbum()->getGenre())
        );
    }

    protected function getTypeAndTracks(): string {
        $album = $this->getAlbum();
        $type = $album->getType();
        $typeHumanReadable = $album->getTypeHumanReadable();

        if (in_array($type, [AlbumModel::ALBUM_TYPE_SINGLE, AlbumModel::ALBUM_TYPE_DOUBLE_SINGLE], true)) {
            return $typeHumanReadable;
        }
        try {
            $tracksCount = count($album->getTracks());
        } catch (Exception) {
            return '';
        }

        return sprintf('%s • %s %s', $typeHumanReadable, $tracksCount, $this->getTrackWordForm($tracksCount));
    }
}
