<?php

declare(strict_types=1);

namespace BC\DTO;

readonly class PageDTO {
    public function __construct(
        public string $title,
        /** Ссылка на страницу в админке */
        public string $url,
        /** Ссылка на публичную страницу сайта; пустая, если тип страницы неизвестен */
        public string $publicUrl = ''
    ) {
    }
}
