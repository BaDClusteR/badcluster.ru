<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\DTO\Settings\SettingsDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Core\Comment\CommentSpamFilter;
use BC\Model\Config;

#[Docs\Group('Settings')]
class Settings extends AEndpoint {
    #[API\Endpoint(path: 'settings', method: 'GET')]
    public function getSettings(): SettingsDTO {
        return new SettingsDTO(
            commentBlacklist: Config::getConfig(CommentSpamFilter::CONFIG_NAME)
        );
    }

    #[API\Endpoint(path: 'settings', method: 'PUT')]
    public function saveSettings(
        #[API\Parameter(source: 'body', name: 'commentBlacklist')]
        string $commentBlacklist
    ): SuccessfulResultDTO {
        $this->handleWithException(
            static function () use ($commentBlacklist) {
                Config::setConfig(CommentSpamFilter::CONFIG_NAME, $commentBlacklist);
            }
        );

        return new SuccessfulResultDTO();
    }
}
