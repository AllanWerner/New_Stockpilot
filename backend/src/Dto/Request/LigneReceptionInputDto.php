<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LigneReceptionInputDto
{
    #[Assert\Positive]
    public int $idLigneCommande = 0;

    #[Assert\PositiveOrZero]
    public int $quantiteRecue = 0;
}
