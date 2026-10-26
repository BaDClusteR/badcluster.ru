<?php

namespace BC\Modules\Books\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Modules\Books\Widget\List\BookList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class BookListPage extends APage {
    /**
     * @var array<mixed> The groups of BookList: [title => Book[]].
     */
    private array $bookGroups = [];

    public function getHeader(): string {
        return 'Книги';
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

        $groups = $this->context['groups'] ?? null;

        if (!empty($groups) && is_array($groups)) {
            $this->bookGroups = $groups;
        }
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('books', 'css/modules/Books/books.css'),
            new AssetDTO('book-cover', 'css/modules/Books/book-cover.css'),
            new AssetDTO('materials', 'css/common/materials.css')
        ];
    }

    /**
     * @return string[]
     */
    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = 'materials';
        $list[] = 'books';
        $list[] = 'book-cover';

        return $list;
    }
}
