<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class InvalidCredentialsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Identifiants invalides.');
    }
}
