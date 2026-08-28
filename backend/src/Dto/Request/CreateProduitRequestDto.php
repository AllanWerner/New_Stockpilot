<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProduitRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public string $nom = '';

    #[Assert\Length(max: 30)]
    public ?string $codeBarre = null;

    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public string $prixAchat = '0';

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $unite = 'piece';

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $idCategorie = 0;

    /**
     * Optional: if provided, a Stock row is created/updated for this boutique
     * with the given threshold (StockService::definirSeuil). Ignored when
     * toutesBoutiques is true.
     */
    public ?int $idBoutique = null;

    /**
     * Gérant-only: assign this product's stock to every boutique at once
     * (each starting at quantiteActuelle = 0), instead of a single one.
     */
    public bool $toutesBoutiques = false;

    #[Assert\PositiveOrZero]
    public int $seuilReappro = 0;

    #[Assert\PositiveOrZero]
    public int $quantiteCommandeReco = 0;
}
