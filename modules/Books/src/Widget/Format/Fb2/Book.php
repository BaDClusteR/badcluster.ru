<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2;

use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\FormatterTrait;
use BC\Core\Trait\PathsProviderTrait;
use BC\Model\Media;
use BC\Modules\Books\Model\Chapter;
use BC\Modules\Books\Widget\Format\ABook;

class Book extends ABook {
    use FormatterTrait;
    use DateConverterTrait;
    use PathsProviderTrait;

    /**
     * @var list<array{id: string, content: string}>
     *
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    private array $images = [];

    /**
     * @return list<array{id: string, content: string}>
     */
    protected function getImages(): array {
        return $this->images;
    }

    public function addImage(string $filename): string {
        $id = 'img_' . count($this->images);
        $content = file_get_contents($filename);

        $this->images[] = [
            'id'      => $id,
            'content' => ($content === false) ? '' : $content,
        ];

        return $id;
    }

    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/book.phtml';
    }

    protected function getBookAnnotation(): string {
        return $this->getFormatter()->formatAsHtml($this->xml($this->getBook()->getAnnotation()));
    }

    /**
     * Обычный текст (названия, имена, жанр) для XML: «Tom & Jerry» иначе ломает файл
     */
    public function xml(string $text): string {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function getImageThumbnailPath(Media $image): string {
        $thumbnail = $image->getThumbnail(1500, 'image/jpeg');
        if ($thumbnail) {
            // Путь к файлу, а не URL: обложка встраивается в книгу через file_get_contents()
            return $thumbnail->getLocalPath();
        }

        return $image->getLocalPath();
    }

    /**
     * @return list<array{firstname: string, lastname: string}>
     */
    protected function getBookAuthors(): array {
        return array_map(
            fn (string $author): array => $this->splitAuthorName($author),
            $this->getBook()->getAuthors()
        );
    }

    /**
     * @param string $name
     *
     * @return array{firstname: string, lastname: string}
     */
    private function splitAuthorName(string $name): array {
        $pieces = array_filter(
            explode(' ', $name),
            static fn (string $piece): bool => !empty(trim($piece))
        );

        $pieces = array_values($pieces);

        $firstname = (string) array_shift($pieces);
        $lastname = implode(' ', $pieces);

        return [
            'firstname' => $firstname,
            'lastname'  => $lastname
        ];
    }

    /**
     * Главы по частям: часть начинается с главы, у которой задан part, и идет до следующей такой.
     * Главы до первой части — в группе с пустым part. В FB2 секция без текста и без вложенных
     * секций не проходит схему, поэтому главы части вкладываются в ее секцию.
     *
     * @return list<array{part: string, chapters: list<Chapter>}>
     */
    protected function getChapterGroups(): array {
        $groups = [];
        $part = '';
        $chapters = [];

        foreach ($this->getChapters() as $chapter) {
            if ($chapter->getPart()) {
                if ($chapters) {
                    $groups[] = ['part' => $part, 'chapters' => $chapters];
                }

                $part = $chapter->getPart();
                $chapters = [];
            }

            $chapters[] = $chapter;
        }

        if ($chapters) {
            $groups[] = ['part' => $part, 'chapters' => $chapters];
        }

        return $groups;
    }

    protected function getBlockRenderer(): \BC\Widget\Blocks {
        return new Blocks();
    }

    protected function getChapterContent(Chapter $chapter): string {
        $content = $this->renderer->render([...$chapter->getContent(), 'book' => $this]);
        /** @noinspection HtmlUnknownAttribute */
        $contentWithNotes = $this->notesExtractor->extractNotes(
            $content,
            count($this->getNotes()) + 1,
            '<a l:href="#n{{index}}" type="note">[{{index}}]</a>'
        );

        foreach ($contentWithNotes->notes as $note) {
            $this->addNote($note->content, $note->id);
        }

        return $contentWithNotes->content;
    }

    protected function getFormatType(): string {
        return 'fb2';
    }

    /** @noinspection HtmlUnknownAttribute */
    protected function getPostfix(): string {
        $template = $this->getBookFormat()?->getPostfix();

        if (!$template) {
            $template = $this->getBook()->isTranslation()
                ? '<p><emphasis>Перевел BaD ClusteR</emphasis></p><p><emphasis>{{start_year}} - {{end_year}}, <a l:href="https://badcluster.ru">https://badcluster.ru</a></emphasis></p>'
                : '<p><emphasis>BaD ClusteR</emphasis></p><p><emphasis>{{start_year}} - {{end_year}}, <a l:href="https://badcluster.ru">https://badcluster.ru</a></emphasis></p>';
        }

        return str_replace(
            ['{{start_year}}', '{{end_year}}'],
            [(string) $this->getStartYear(), (string) $this->getEndYear()],
            $template
        );
    }
}
