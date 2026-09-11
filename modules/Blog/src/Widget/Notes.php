<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget;

use BC\Core\Trait\AuthTrait;
use BC\Core\Trait\DateConverterTrait;
use BC\Modules\Blog\Model\Note;
use BC\Modules\Blog\Provider\INotesProvider;
use BC\Widget\AWidget;
use DateTime;
use Runway\Singleton\Container;

class Notes extends AWidget {
    use AuthTrait;
    use DateConverterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Blog/notes.phtml';
    }

    /**
     * @return iterable<Note>
     */
    protected function getNotes(): iterable {
        return $this->getNotesProvider()->getNotes(
            !$this->getAuth()->isAuthenticated()
        );
    }

    protected function getDateValue(?DateTime $date): string {
        return $date
            ? $this->getDateConverter()->toIsoFormat($date)
            : '';
    }

    protected function getHumanReadableDate(?DateTime $date): string {
        return $date
            ? $this->getDateConverter()->toShortForm($date)
            : '';
    }

    private function getNotesProvider(): INotesProvider {
        return Container::getInstance()->getService(INotesProvider::class);
    }
}
