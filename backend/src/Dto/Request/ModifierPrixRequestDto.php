<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ModifierPrixRequestDto
{
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public string $prixAchat = '0';
}
