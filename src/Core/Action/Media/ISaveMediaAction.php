<?php

declare(strict_types=1);

namespace BC\Core\Action\Media;

use BC\Core\Action\DTO\SaveMediaRequest;
use BC\Core\Action\DTO\SaveMediaResponse;

interface ISaveMediaAction {
    public function run(SaveMediaRequest $request): SaveMediaResponse;
}