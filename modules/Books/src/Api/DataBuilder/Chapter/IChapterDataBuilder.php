<?php

namespace BC\Modules\Books\Api\DataBuilder\Chapter;

use BC\Modules\Books\Api\DTO\Chapter\ChapterDTO;
use BC\Modules\Books\Api\DTO\Chapter\ChapterRowDTO;
use BC\Modules\Books\Model\Chapter;

interface IChapterDataBuilder {
    public function buildRow(Chapter $chapter): ChapterRowDTO;

    public function buildEntity(Chapter $chapter): ChapterDTO;
}
