<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AjustementStockRequestDto
{
    #[Assert\Positive]
    public int $idBoutique = 0;

    #[Assert\NotEqualTo(0)]
    public int $quantite = 0;
}
