<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LigneCommandeInputDto
{
    #[Assert\Positive]
    public int $idProduit = 0;

    #[Assert\Positive]
    public int $quantiteCommandee = 0;

    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public string $prixUnitaire = '0';
}
