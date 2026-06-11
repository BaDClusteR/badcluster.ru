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
     * @var array{id: string, content: string}
     *
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    private array $images = [];

    protected function getImages(): array {
        return $this->images;
    }

    public function addImage(string $filename): string {
        $id = 'img_' . count($this->images);
        $content = file_get_contents($filename);

        $this->images[] = [
            'id'      => $id,
            'content' => $content,
        ];

        return $id;
    }

    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/book.phtml';
    }

    protected function getBookAnnotation(): string {
        return $this->getFormatter()->formatAsHtml($this->getBook()->getAnnotation());
    }

    protected function getImageThumbnailPath(Media $image): string {
        $thumbnail = $image->getThumbnail(1500, 'image/jpeg');
        if ($thumbnail) {
            return $thumbnail->getWebPath();
        }

        return $image->getLocalPath();
    }

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
            [$this->getStartYear(), $this->getEndYear()],
            $template
        );
    }
}
