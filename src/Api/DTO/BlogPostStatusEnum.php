<?php

namespace BC\Api\DTO;

enum BlogPostStatusEnum: string {
    case PUBLISHED = 'published';

    case DRAFT = 'draft';
}
