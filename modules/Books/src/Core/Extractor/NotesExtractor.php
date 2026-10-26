<?php

namespace BC\Modules\Books\Core\Extractor;

use BC\Modules\Books\Core\DTO\ExtractedNotesWithContentDTO;
use BC\Modules\Books\Core\DTO\NoteDTO;
use Runway\Exception\RuntimeException;

class NotesExtractor implements INotesExtractor {
    /**
     * @throws RuntimeException The regular expression failed (e.g. the backtrack limit).
     */
    public function extractNotes(
        string $content,
        int $startIndex = 1,
        string $template = "<sup><a href='#n{{index}}' id='n{{index}}_link' class='note'><span class='note__text'>[{{index}}]</span><span class='tooltip note__tooltip'>{{content}}</span></a></sup>"
    ): ExtractedNotesWithContentDTO {
        $notes = [];
        $i = $startIndex;

        $content = preg_replace_callback(
            '/<sup>\[(.*?)]<\/sup>/',
            static function (array $matches) use (&$notes, &$i, $template): string {
                $noteContent = $matches[1];
                $notes[] = new NoteDTO("n$i", $noteContent);

                $result = str_replace(
                    ['{{index}}', '{{content}}'],
                    [(string) $i, $noteContent],
                    $template
                );
                $i++;

                return $result;
            },
            $content
        );

        if ($content === null) {
            throw new RuntimeException('Cannot extract the notes: ' . preg_last_error_msg());
        }

        return new ExtractedNotesWithContentDTO($content, $notes);
    }
}
