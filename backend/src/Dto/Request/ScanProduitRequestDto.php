<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ScanProduitRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    public string $codeBarre = '';

    /**
     * Optional: if provided, a Stock row is created/updated for this boutique
     * with the given threshold (StockService::definirSeuil).
     */
    public ?int $idBoutique = null;

    #[Assert\PositiveOrZero]
    public int $seuilReappro = 0;

    #[Assert\PositiveOrZero]
    public int $quantiteCommandeReco = 0;
}
