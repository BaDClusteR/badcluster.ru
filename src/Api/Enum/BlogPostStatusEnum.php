<?php

namespace BC\Api\Enum;

enum BlogPostStatusEnum: string {
    case PUBLISHED = 'published';

    case DRAFT = 'draft';
}
