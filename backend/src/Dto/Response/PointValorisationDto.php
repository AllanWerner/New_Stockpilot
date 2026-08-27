<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointValorisationDto
{
    public function __construct(
        public readonly string $date,
        public readonly string $valeur,
    ) {
    }
}
