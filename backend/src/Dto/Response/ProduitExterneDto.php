<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ProduitExterneDto
{
    public function __construct(
        public readonly string $nom,
        public readonly ?string $categorieSuggeree,
    ) {
    }
}
