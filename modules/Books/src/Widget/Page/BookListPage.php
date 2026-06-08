<?php

namespace BC\Modules\Books\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Widget\List\BookList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class BookListPage extends APage {
    /**
     * @var array{0: string, 1: Book[]}
     */
    private array $bookGroups;

    public function getHeader(): string {
        return 'Библиотека';
    }

    public function getMetaDescription(): string {
        return implode('\n', $this->getDescription());
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/books';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [
            'Переводы книг и мои собственные попытки в литературное творчество.'
        ];
    }

    public function getMainWidget(): AWidget {
        return new BookList(['groups' => $this->bookGroups]);
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (!empty($this->context['groups'])) {
            $this->bookGroups = $this->context['groups'];
        }
    }

    public static function getAssets(): array {
        $list = parent::getAssets();

        $list[] = new AssetDTO('books', 'css/modules/Books/books.css');
        $list[] = new AssetDTO('book-cover', 'css/modules/Books/book-cover.css');
        $list[] = new AssetDTO('materials', 'css/common/materials.css');

        return $list;
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = 'materials';
        $list[] = 'books';
        $list[] = 'book-cover';

        return $list;
    }
}
