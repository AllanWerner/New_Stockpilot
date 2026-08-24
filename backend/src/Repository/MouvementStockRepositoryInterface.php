<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MouvementStock;

interface MouvementStockRepositoryInterface
{
    public function save(MouvementStock $mouvement): void;
}
