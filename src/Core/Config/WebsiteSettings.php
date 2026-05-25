<?php

declare(strict_types=1);

namespace BC\Core\Config;

use BC\Core\DTO\AdminContactsDTO;

readonly class WebsiteSettings implements IWebsiteSettings {
    public function __construct(
        private string $webRoot,
        private string $adminEmail,
        private string $adminTelegram,
        private string $adminSteam,
        private string $adminGithub
    ) {
    }

    public function getWebRoot(): string {
        return $this->webRoot;
    }

    public function getImageBreakpoints(): array {
        return [
            450 => 500,
            -1  => 1000,
        ];
    }

    public function getAdminContacts(): AdminContactsDTO {
        return new AdminContactsDTO(
            email: $this->adminEmail,
            telegram: $this->adminTelegram,
            steam: $this->adminSteam,
            github: $this->adminGithub
        );
    }
}
