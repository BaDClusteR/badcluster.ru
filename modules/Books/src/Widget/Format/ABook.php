<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Modules\Books\Core\Extractor\INotesExtractor;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use BC\Widget\AWidget;
use BC\Widget\Blocks;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;
use Runway\Singleton\Container;

abstract class ABook extends AWidget {
    use WebsiteSettingsTrait;

    /**
     * @var Chapter[]|null
     */
    private ?array $chapters = null;

    abstract protected function getBlockRenderer(): Blocks;

    abstract protected function getFormatType(): string;

    /**
     * @var array{id: string, content: string}
     */
    protected array $notes = [];

    protected Blocks $renderer;

    protected INotesExtractor $notesExtractor;

    public function __construct(array $context = []) {
        parent::__construct($context);

        $this->renderer = $this->getBlockRenderer();
        $this->notesExtractor = $this->getNotesExtractor();
    }

    protected function getNotes(): array {
        return $this->notes;
    }

    protected function addNote(string $content, string $id): string {
        $this->notes[] = [
            'id'      => $id,
            'content' => $content,
        ];

        return $id;
    }

    protected function getBook(): BookModel {
        return $this->context['book'];
    }

    /**
     * @return Chapter[]
     */
    protected function getChapters(): array {
        try {
            $this->chapters ??= Chapter::getQueryBuilder()
                                       ->where('book_id = :book_id')
                                       ->andWhere('published = :published')
                                       ->orderBy('position', 'ASC')
                                       ->setVariable('book_id', $this->getBook()->getId())
                                       ->setVariable('published', true)
                                       ->getEntities();
        } catch (Exception) {
            $this->chapters = [];
        }

        return $this->chapters;
    }

    protected function getNotesExtractor(): INotesExtractor {
        return Container::getInstance()->getService(INotesExtractor::class);
    }

    protected function getStartYear(): int {
        return min(
            array_map(
                static fn (Chapter $chapter): int => (int) $chapter->getAddedDate()->format('Y'),
                $this->getChapters()
            )
        );
    }

    protected function getEndYear(): int {
        return max(
            array_map(
                static fn (Chapter $chapter): int => (int) $chapter->getAddedDate()->format('Y'),
                $this->getChapters()
            )
        );
    }

    protected function getBookFormat(): ?BookFormat {
        $type = $this->getFormatType();

        try {
            return array_find(
                $this->getBook()->getFormats(),
                static fn ($format) => $format->getType() === $type
            );
        } catch (Exception) {
            return null;
        }
    }
}
