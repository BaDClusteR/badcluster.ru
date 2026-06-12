<?php

namespace BC\Modules\Books\Core\Extractor;

use BC\Modules\Books\Core\DTO\ExtractedNotesWithContentDTO;

interface INotesExtractor {
    public function extractNotes(
        string $content,
        int $startIndex = 1,
        string $template = "<sup><a href='#n{{index}}' id='n{{index}}_link' class='note'><span class='note__text'>[{{index}}]</span><span class='tooltip note__tooltip'>{{content}}</span></a></sup>"
    ): ExtractedNotesWithContentDTO;
}
