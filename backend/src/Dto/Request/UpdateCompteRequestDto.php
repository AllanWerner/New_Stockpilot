<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCompteRequestDto
{
    #[Assert\NotBlank]
    public string $motDePasseActuel = '';

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(min: 8)]
    public ?string $nouveauMotDePasse = null;
}
