<?php

namespace BC\Modules\Books\Core\Extractor;

use BC\Modules\Books\Core\DTO\ExtractedNotesWithContentDTO;
use BC\Modules\Books\Core\DTO\NoteDTO;

class NotesExtractor implements INotesExtractor {
    public function extractNotes(
        string $content,
        int $startIndex = 1,
        string $template = "<sup><a href='#n{{index}}' id='n{{index}}_link'>[{{index}}]</a></sup>"
    ): ExtractedNotesWithContentDTO {
        $notes = [];
        $i = $startIndex;

        $content = preg_replace_callback(
            '/<sup>\[(.*?)]<\/sup>/',
            static function ($matches) use (&$notes, &$i, $template) {
                $notes[] = new NoteDTO(
                    "n$i",
                    (string) ($matches[1] ?? '')
                );

                $result = str_replace('{{index}}', $i, $template);
                $i++;

                return $result;
            },
            $content
        );

        return new ExtractedNotesWithContentDTO($content, $notes);
    }
}
