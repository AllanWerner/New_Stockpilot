<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCommandeRequestDto
{
    #[Assert\Positive]
    public int $idBoutique = 0;

    #[Assert\Positive]
    public int $idFournisseur = 0;

    public ?string $datePrevue = null;

    /**
     * @var LigneCommandeInputDto[]
     */
    #[Assert\Count(min: 1, minMessage: 'La commande doit contenir au moins une ligne.')]
    #[Assert\Valid]
    public array $lignes = [];
}
