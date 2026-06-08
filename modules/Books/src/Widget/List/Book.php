<?php

namespace BC\Modules\Books\Widget\List;

use BC\Core\Converter\IDateConverter;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\BookFormat;
use BC\Widget\AWidget;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

class Book extends AWidget {
    protected function getTemplatePath(): string {
        return 'modules/Books/list/book.phtml';
    }

    protected function getBook(): BookModel {
        return $this->context['book'];
    }

    protected function getDateConverter(): IDateConverter {
        return Container::getInstance()->getService(IDateConverter::class);
    }

    /**
     * @return BookFormat[]
     */
    protected function getBookFormats(): array {
        try {
            return array_values(
                array_filter(
                    $this->getBook()->getFormats(),
                    static fn (BookFormat $format): bool => $format->getAllowed()
                )
            );
        } catch (Exception) {
            return [];
        }
    }
}
