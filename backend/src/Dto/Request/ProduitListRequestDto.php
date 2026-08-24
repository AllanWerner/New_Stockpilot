<?php

declare(strict_types=1);

namespace App\Dto\Request;

final class ProduitListRequestDto
{
    public ?string $nom = null;

    public ?int $idCategorie = null;

    public ?int $idFournisseur = null;

    public ?int $idBoutique = null;

    /**
     * One of: 'rupture', 'critique', 'ok' — filters on the Stock row for idBoutique.
     * Ignored if idBoutique is not provided, or if the value isn't one of the three above.
     */
    public ?string $statutStock = null;

    public int $page = 1;

    public int $limit = 20;

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $dto = new self();
        $dto->nom = isset($query['nom']) && '' !== $query['nom'] ? (string) $query['nom'] : null;
        $dto->idCategorie = isset($query['idCategorie']) ? (int) $query['idCategorie'] : null;
        $dto->idFournisseur = isset($query['idFournisseur']) ? (int) $query['idFournisseur'] : null;
        $dto->idBoutique = isset($query['idBoutique']) ? (int) $query['idBoutique'] : null;
        $dto->statutStock = isset($query['statutStock']) && '' !== $query['statutStock'] ? (string) $query['statutStock'] : null;
        $dto->page = isset($query['page']) ? max(1, (int) $query['page']) : 1;
        $dto->limit = isset($query['limit']) ? min(100, max(1, (int) $query['limit'])) : 20;

        return $dto;
    }
}
