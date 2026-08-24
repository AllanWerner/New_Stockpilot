<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Entity\Enum\PosteEmploye;
use Symfony\Component\Validator\Constraints as Assert;

final class AffecterEmployeRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $idEmploye = 0;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PosteEmploye::class, 'values'])]
    public string $posteEmploye = '';
}
