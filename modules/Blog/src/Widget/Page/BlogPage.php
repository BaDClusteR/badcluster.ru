<?php

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Blog\Widget\Posts;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class BlogPage extends APage
{
    /**
     * @inheritDoc
     */
    public static function getAssets(): array
    {
        return [
            new AssetDTO(
                'posts',
                'css/modules/Blog/posts.css'
            )
        ];
    }

    public function getHeader(): string
    {
        return "Блог";
    }

    public function getTitle(): string
    {
        return $this->getHeader() . " :: " . parent::getTitle();
    }

    public function getDescription(): array
    {
        return [
            "Мысли о фильмах, играх, и капля цифровой археологии. Пишу о пройденном и просмотренном, по настроению копаюсь в лоре, разбираю игровые секреты."
        ];
    }

    public function getMainWidget(): AWidget
    {
        return new Posts();
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'posts';

        return $list;
    }
}
