<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class DashboardDto
{
    /**
     * @param PointValorisationDto[]         $evolutionValorisation
     * @param ProduitSousSeuilCritiqueDto[]  $produitsSousSeuilCritique
     */
    public function __construct(
        public readonly string $valeurStock,
        public readonly int $referencesEnRupture,
        public readonly int $sousSeuilCritique,
        public readonly int $commandesEnCours,
        public readonly array $evolutionValorisation,
        public readonly array $produitsSousSeuilCritique,
    ) {
    }
}
