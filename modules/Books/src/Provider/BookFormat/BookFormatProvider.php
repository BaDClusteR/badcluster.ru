<?php

namespace BC\Modules\Books\Provider\BookFormat;

use BC\Modules\Books\Core\DTO\BookFormatDTO;
use BC\Modules\Books\Format\IBookFormat;
use Runway\Singleton\Container;

class BookFormatProvider implements IBookFormatProvider {
    /**
     * @inheritDoc
     */
    public function getFormats(): array {
        $result = [];

        /** @var IBookFormat $format */
        foreach (Container::getInstance()->getServicesByTag('book_format') as $format) {
            $result[] = new BookFormatDTO($format->getType());
        }

        return $result;
    }
}
