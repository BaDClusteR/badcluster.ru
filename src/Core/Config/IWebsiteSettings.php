<?php

declare(strict_types=1);

namespace BC\Core\Config;

use BC\Core\DTO\AdminContactsDTO;

interface IWebsiteSettings {
    public function getWebRoot(): string;

    public function getImageBreakpoints(): array;

    public function getAdminContacts(): AdminContactsDTO;
}
