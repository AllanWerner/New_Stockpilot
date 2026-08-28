<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Categorie;

interface CategorieRepositoryInterface
{
    public function findByNom(string $nom): ?Categorie;

    public function find(int $id): ?Categorie;

    /**
     * @return Categorie[]
     */
    public function findAll(): array;

    public function save(Categorie $categorie): void;
}
