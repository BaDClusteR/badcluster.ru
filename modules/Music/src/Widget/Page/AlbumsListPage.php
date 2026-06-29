<?php

declare(strict_types=1);

namespace BC\Modules\Music\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Music\Widget\AlbumsList\AlbumsList;
use BC\Modules\Music\Model\Album;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class AlbumsListPage extends APage {
    public function getHeader(): string {
        return 'Музыка';
    }

    public function getMetaDescription(): string {
        return 'Музыкальный проект SBC. Песни самых разных жанров и настроений: от мемных баллад про айтишную боль до брутального рока про Думгая. Нейросети пишут звук, я пишу промпты и сочиняю тексты.';
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/music';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [
            'Музыкальный проект SBC. Песни самых разных жанров и настроений: хулиганский рок про тяжелую жизнь проводника, мемные баллады про айтишную боль, меланхоличный синтвейв, брутальный рок по Думу и веселый троллинг от лица собаки из Silent Hill 2.',
            'Нейросети пишут звук, я пишу промпты и сочиняю тексты. Жанровых рамок нет — только чистое творчество, эксперименты со звуком и алгоритмами',
        ];
    }

    public function getMainWidget(): AWidget {
        return new AlbumsList(['albums' => $this->getAlbums()]);
    }

    /**
     * @return Album[]
     */
    protected function getAlbums(): array {
        return (array) ($this->context['albums'] ?? []);
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'music',
                'css/modules/Music/music.css'
            ),
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'music';

        return $list;
    }
}
