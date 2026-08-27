<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class EmployeDejaExistantException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Un compte avec cet email existe déjà.');
    }
}
