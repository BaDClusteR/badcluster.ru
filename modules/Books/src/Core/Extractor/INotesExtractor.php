<?php

namespace BC\Modules\Books\Core\Extractor;

use BC\Modules\Books\Core\DTO\ExtractedNotesWithContentDTO;

interface INotesExtractor {
    public function extractNotes(
        string $content,
        int $startIndex = 1,
        string $template = "<sup><a href='#n{{index}}' id='n{{index}}_link'>[{{index}}]</a></sup>"
    ): ExtractedNotesWithContentDTO;
}
