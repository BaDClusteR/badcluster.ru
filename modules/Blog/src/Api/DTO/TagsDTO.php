<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DTO;

use ApiPlatform\Attribute\Docs;

class TagsDTO {
    /**
     * @param TagDTO[] $tags
     */
    public function __construct(
        #[Docs\Property(childrenType: TagDTO::class)]
        public array $tags
    ) {
    }
}
