<?php

namespace BC\Core\Action\DTO;

readonly class GetCommentsRequest {
    public function __construct(
        public string $pageType,
        public int $pageId,
        public bool $includeWaitingForApproval,
        public bool $includeDeclined
    ) {
    }
}
