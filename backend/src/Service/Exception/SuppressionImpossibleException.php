<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class SuppressionImpossibleException extends \RuntimeException
{
    public function __construct(string $message = 'Suppression impossible : des données liées existent. Désactivez plutôt cet élément.')
    {
        parent::__construct($message);
    }
}
