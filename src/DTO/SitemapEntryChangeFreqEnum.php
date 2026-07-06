<?php

declare(strict_types=1);

namespace BC\DTO;

enum SitemapEntryChangeFreqEnum: string {
    case ALWAYS = 'always';
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case NEVER = 'never';
}
