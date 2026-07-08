<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Redirect;

use BC\Api\DTO\Redirect\RedirectDTO;
use BC\Api\DTO\Redirect\RedirectRowDTO;
use BC\Model\Redirect;

interface IRedirectDataBuilder {
    public function buildRow(Redirect $redirect): RedirectRowDTO;

    public function buildEntity(Redirect $redirect): RedirectDTO;
}