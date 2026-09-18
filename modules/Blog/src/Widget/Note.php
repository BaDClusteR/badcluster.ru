<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget;

use BC\Core\Trait\DateConverterTrait;
use BC\Modules\Blog\Model\Note as NoteModel;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;
use DateTime;
use Runway\Exception\RuntimeException;

class Note extends AWidget {
    use DateConverterTrait;

    protected ?NoteModel $note = null {
        get {
            return $this->note;
        }
    }

    protected ?APage $page = null {
        get {
            return $this->page;
        }
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (!$this->note && !(($context['note'] ?? null) instanceof NoteModel)) {
            throw new RuntimeException(__METHOD__ . ': note is not set or not an instance of ' . NoteModel::class);
        }

        if (($this->context['note'] ?? null) instanceof NoteModel) {
            $this->note = $this->context['note'];
        }

        if (($this->context['page'] ?? null) instanceof APage) {
            $this->page = $this->context['page'];
        }
    }

    public function render(array $context = []): string {
        if (!$this->note) {
            throw new RuntimeException(__METHOD__ . ': note is not set');
        }

        return parent::render($context);
    }

    protected function getTemplatePath(): string {
        return 'modules/Blog/note.phtml';
    }

    protected function getDateTime(DateTime $dt): string {
        return $this->getDateConverter()->toIsoFormat($dt);
    }

    protected function getHumanReadableDateTime(DateTime $dt): string {
        return $this->getDateConverter()->toShortForm(
            $dt->getTimestamp(),
        );
    }
}
