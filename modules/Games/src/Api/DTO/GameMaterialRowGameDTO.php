<?php

namespace BC\Modules\Games\Api\DTO;

readonly class GameMaterialRowGameDTO {
    public function __construct(
        public int $id,
        public string $title
    ) {
    }
}
