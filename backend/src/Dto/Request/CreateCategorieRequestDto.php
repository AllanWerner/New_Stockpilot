<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCategorieRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $nom = '';
}
