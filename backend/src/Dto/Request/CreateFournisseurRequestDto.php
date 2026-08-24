<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateFournisseurRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $nom = '';

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $emailContact = null;

    #[Assert\Length(max: 30)]
    public ?string $telephone = null;

    #[Assert\PositiveOrZero]
    public ?int $delaiLivraisonJours = null;
}
