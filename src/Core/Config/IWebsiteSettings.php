<?php

declare(strict_types=1);

namespace BC\Core\Config;

use BC\Core\DTO\AdminContactsDTO;

interface IWebsiteSettings {
    public function getWebRoot(): string;

    /**
     * @return array<int, int> The max width of the viewport (-1 for any other) => the width of the image.
     */
    public function getImageBreakpoints(): array;

    public function getAdminContacts(): AdminContactsDTO;
}
