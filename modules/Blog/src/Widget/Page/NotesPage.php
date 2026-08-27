<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Blog\Widget\Notes;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class NotesPage extends APage {
    public function getHeader(): string {
        return 'Дамп памяти';
    }

    public function getMetaDescription(): string {
        return 'Короткие заметки обо всём подряд — мысли, находки, спонтанные идеи и наброски. 100% AI-free.';
    }

    public function getDescription(): array {
        return [
            'Короткие заметки обо всём подряд — мысли, находки, спонтанные идеи и наброски. 100% AI-free. Ни одной правки от нейросетей, только мой поток сознания :)',
        ];
    }

    public function getMainWidget(): AWidget {
        return new Notes();
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/notes';
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'notes',
                'css/modules/Blog/notes.css'
            ),
            new AssetDTO(
                'toggler',
                'css/modules/Blog/toggler.css'
            ),
        ];
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = 'notes';
        $list[] = 'toggler';

        return $list;
    }
}
