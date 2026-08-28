<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Entity\Enum\RoleEmploye;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEmployeRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $nom = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $prenom = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $motDePasse = '';

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [RoleEmploye::class, 'values'])]
    public string $role = '';
}
