<?php

namespace BC\Modules\Blog\Api\DataBuilder\Tag;

use BC\Modules\Blog\Api\DTO\TagDTO;
use BC\Modules\Blog\Api\DTO\TagRowDTO;
use BC\Modules\Blog\Model\Tag;

interface ITagDataBuilder {
    public function buildRow(array $tag): TagRowDTO;

    public function buildEntity(Tag $tag): TagDTO;
}
