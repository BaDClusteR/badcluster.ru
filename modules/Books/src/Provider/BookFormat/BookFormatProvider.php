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

        foreach ($this->getFormatters() as $formatter) {
            $result[] = new BookFormatDTO(
                $formatter->getType(),
                $formatter
            );
        }

        return $result;
    }

    /**
     * @return list<IBookFormat>
     */
    private function getFormatters(): array {
        return array_values(
            array_filter(
                Container::getInstance()->getServicesByTag('book_format'),
                static fn (mixed $service): bool => $service instanceof IBookFormat
            )
        );
    }

    public function getFormat(string $type): ?IBookFormat {
        return array_find(
            $this->getFormatters(),
            static fn (IBookFormat $format): bool => $format->getType() === $type
        );
    }
}
