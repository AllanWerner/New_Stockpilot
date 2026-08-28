<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class CategorieDejaExistanteException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Une catégorie avec ce nom existe déjà.');
    }
}
