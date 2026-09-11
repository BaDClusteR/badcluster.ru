<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Action\Validator;

use BC\Modules\Blog\Core\Action\DTO\CreateNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\SaveNoteRequest;
use BC\Modules\Blog\Core\Action\DTO\ValidatorResponse;

interface INoteValidator {
    public function validate(SaveNoteRequest|CreateNoteRequest $request): ValidatorResponse;
}
