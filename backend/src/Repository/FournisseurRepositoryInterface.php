<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fournisseur;

interface FournisseurRepositoryInterface
{
    public function find(int $id): ?Fournisseur;

    public function save(Fournisseur $fournisseur): void;
}
