<?php

declare(strict_types=1);

namespace BC\Api\Enum;

enum BlogPostStatusEnum: string {
    case PUBLISHED = 'published';

    case DRAFT = 'draft';
}
