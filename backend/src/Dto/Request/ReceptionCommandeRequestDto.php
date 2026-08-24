<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ReceptionCommandeRequestDto
{
    /**
     * @var LigneReceptionInputDto[]
     */
    #[Assert\Count(min: 1, minMessage: 'La réception doit porter sur au moins une ligne.')]
    #[Assert\Valid]
    public array $lignes = [];
}
