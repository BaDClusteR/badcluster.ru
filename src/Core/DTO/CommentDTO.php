<?php

declare(strict_types=1);

namespace BC\Core\DTO;

use DateTime;

readonly class CommentDTO {
    /**
     * @param self[] $children
     */
    public function __construct(
        public int $id,
        public DateTime $date,
        public string $name,
        public ?string $email,
        public string $comment,
        public string $ip,
        public bool $isApproved,
        public bool $isDeclined,
        public array $children
    ) {
    }
}
