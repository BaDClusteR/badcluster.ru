<?php

namespace BC\Core\Action\DTO;

readonly class GetCommentsRequest {
    public function __construct(
        public string $pageType,
        public int $pageId,
        public ?bool $includePending = null, // NULL means default: include only if logged as admin
        public ?bool $includeDeclined = null // NULL means default: include only if logged as admin
    ) {
    }
}
