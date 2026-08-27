<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateBoutiqueRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $nom = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $adresse = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $ville = '';
}
