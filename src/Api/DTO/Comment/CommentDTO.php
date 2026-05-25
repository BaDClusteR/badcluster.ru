<?php

declare(strict_types=1);

namespace BC\Api\DTO\Comment;

readonly class CommentDTO {
    public function __construct(
        public string $date,
        public string $dateHumanReadable,
        public string $name,
        public string $email,
        public string $comment,
        public string $page,
        public string $pageLink,
        public GeoIpDTO $geoIp,
        public ?CommentParentDTO $parent,
        public string $status
    ) {
    }
}
