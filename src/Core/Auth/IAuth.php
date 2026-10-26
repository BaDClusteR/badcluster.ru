<?php

declare(strict_types=1);

namespace BC\Core\Auth;

interface IAuth {
    public function isAuthenticated(): bool;
}
