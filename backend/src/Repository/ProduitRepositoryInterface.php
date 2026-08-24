<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Produit;

interface ProduitRepositoryInterface
{
    public function findByCodeBarre(string $codeBarre): ?Produit;

    public function find(int $id): ?Produit;

    public function save(Produit $produit): void;
}
