<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Redirect;

use BC\Api\DTO\Redirect\RedirectDTO;
use BC\Api\DTO\Redirect\RedirectRowDTO;
use BC\Model\Redirect;

readonly class RedirectDataBuilder implements IRedirectDataBuilder {
    public function buildRow(Redirect $redirect): RedirectRowDTO {
        return new RedirectRowDTO(
            $redirect->getId(),
            $redirect->getPath(),
            $redirect->getCode(),
            $redirect->getDestination() ?? ''
        );
    }

    public function buildEntity(Redirect $redirect): RedirectDTO {
        return new RedirectDTO(
            path: $redirect->getPath(),
            code: $redirect->getCode(),
            destination: $redirect->getDestination() ?? ''
        );
    }
}