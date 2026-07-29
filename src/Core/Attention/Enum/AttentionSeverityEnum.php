<?php

declare(strict_types=1);

namespace BC\Core\Attention\Enum;

enum AttentionSeverityEnum: string {
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
