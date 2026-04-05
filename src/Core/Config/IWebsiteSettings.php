<?php

namespace BC\Core\Config;

interface IWebsiteSettings
{
    public function getWebRoot(): string;

    public function getImageBreakpoints(): array;
}
