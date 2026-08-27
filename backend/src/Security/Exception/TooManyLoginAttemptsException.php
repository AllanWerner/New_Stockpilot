<?php

declare(strict_types=1);

namespace App\Security\Exception;

final class TooManyLoginAttemptsException extends \RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('Trop de tentatives de connexion. Réessayez plus tard.');
    }
}
