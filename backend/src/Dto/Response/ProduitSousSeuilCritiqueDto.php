<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ProduitSousSeuilCritiqueDto
{
    public function __construct(
        public readonly int $idProduit,
        public readonly string $nom,
        public readonly int $idBoutique,
        public readonly string $nomBoutique,
        public readonly int $quantiteActuelle,
        public readonly int $seuilReappro,
        public readonly string $statut,
    ) {
    }
}
