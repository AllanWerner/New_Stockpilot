<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class StockInsuffisantException extends \RuntimeException
{
    public function __construct(int $quantiteDemandee, int $quantiteDisponible)
    {
        parent::__construct(sprintf(
            'Stock insuffisant : %d demandé(s), %d disponible(s).',
            $quantiteDemandee,
            $quantiteDisponible,
        ));
    }
}
