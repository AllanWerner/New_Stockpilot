<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Employe;

interface EmployeRepositoryInterface
{
    public function findByEmail(string $email): ?Employe;

    public function find(int $id): ?Employe;

    /**
     * @return Employe[]
     */
    public function findGerants(): array;

    public function save(Employe $employe): void;
}
