<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Request\ProduitListRequestDto;
use App\Entity\Produit;

interface ProduitRepositoryInterface
{
    public function findByCodeBarre(string $codeBarre): ?Produit;

    public function find(int $id): ?Produit;

    /**
     * @return Produit[]
     */
    public function search(ProduitListRequestDto $criteres): array;

    public function save(Produit $produit): void;
}
