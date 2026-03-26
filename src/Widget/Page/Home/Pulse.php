<?php

namespace BC\Widget\Page\Home;

use BC\Widget\AWidget;

class Pulse extends AWidget
{
    protected function getTemplatePath(): string
    {
        return 'home/pulse.phtml';
    }
}
