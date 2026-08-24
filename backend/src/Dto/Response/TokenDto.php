<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TokenDto
{
    public function __construct(
        public readonly string $token,
        public readonly int $idEmploye,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $role,
    ) {
    }
}
