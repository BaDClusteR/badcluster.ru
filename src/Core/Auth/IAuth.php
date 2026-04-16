<?php

namespace BC\Core\Auth;

interface IAuth
{
    public function isAuthenticated(): bool;

    public function checkCredentials(string $login, string $password): bool;
}
