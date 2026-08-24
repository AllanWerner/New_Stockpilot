<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Employe;

interface EmployeRepositoryInterface
{
    public function findByEmail(string $email): ?Employe;

    public function find(int $id): ?Employe;

    public function save(Employe $employe): void;
}
