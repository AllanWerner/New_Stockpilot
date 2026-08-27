<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class QuantiteRecueInvalideException extends \RuntimeException
{
    public function __construct(int $quantiteRecueTotale, int $quantiteCommandee)
    {
        parent::__construct(sprintf(
            'La quantité reçue (%d) dépasserait la quantité commandée (%d) pour cette ligne.',
            $quantiteRecueTotale,
            $quantiteCommandee,
        ));
    }
}
