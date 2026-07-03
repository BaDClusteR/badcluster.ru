<?php

namespace BC\Widget\Page\About;

use BC\Core\Trait\FormatterTrait;
use BC\Widget\AWidget;
use DateTime;

class AboutMe extends AWidget {
    use FormatterTrait;

    protected function getTemplatePath(): string {
        return 'about/about_me.phtml';
    }

    protected function getMyAge(): string {
        return $this->getAge(
            new DateTime('1991-02-01 11:30:00')
        );
    }

    protected function getWebsiteAge(): string {
        return $this->getAge(
            new DateTime('2005-08-02')
        );
    }

    protected function getAge(DateTime $fromDate): string {
        return $this->getFormatter()->formatAsWordForm(
            new DateTime()->diff($fromDate)->y,
            'год',
            'года',
            'лет'
        );
    }
}
