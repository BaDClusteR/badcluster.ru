<?php

namespace BC\Modules\Books\Api\DataBuilder\Book;

use BC\Modules\Books\Api\DTO\Book\BookDTO;
use BC\Modules\Books\Api\DTO\Book\BookRowDTO;
use BC\Modules\Books\Model\Book;

interface IBookDataBuilder {
    public function buildRow(Book $book): BookRowDTO;

    public function buildEntity(Book $book): BookDTO;
}
