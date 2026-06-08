<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\DTO\CommentsConfigDTO;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Widget\Book as BookWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\Page\APage;
use BC\Widget\Page\IPageWithComments;

class BookPage extends APage implements IPageWithComments {
    protected Book $book;

    public function getHeader(): string {
        return '';
    }

    public function getMetaTitle(): string {
        return $this->book->getTitle() . ' — ' . $this->getMetaTitleBase();
    }

    public function getTitle(): string {
        return $this->book->getTitle() . ' :: ' . $this->getTitleBase();
    }

    public function getMetaDescription(): string {
        return $this->book->getShortAnnotation();
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot() . '/books/' . $this->book->getSlug();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new BookWidget(['book' => $this->book]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebRoot() . '/books',
            text: 'К списку книг'
        );
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $this->book = $this->context['book'];
    }

    public static function getAssets(): array {
        $list = parent::getAssets();

        $list[] = new AssetDTO('book', 'css/modules/Books/book.css');
        $list[] = new AssetDTO('book-cover', 'css/modules/Books/book-cover.css');
        $list[] = new AssetDTO('file', 'css/modules/Books/book-cover.css');

        return $list;
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();

        $list[] = 'file';
        $list[] = 'book';
        $list[] = 'book-cover';

        return $list;
    }

    public function getContentContainerCssClass(): string {
        return parent::getContentContainerCssClass() . ' text-block';
    }

    public function getCommentsConfig(): CommentsConfigDTO {
        return new CommentsConfigDTO(
            comments: $this->getComments('book', $this->book->getId()),
            emptyPhrase: 'Комментариев нет, тихо как в библиотеке. Можно нарушить тишину.',
            pageType: 'book',
            pageId: (string) $this->book->getId()
        );
    }
}
