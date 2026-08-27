<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Notification;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;

    public function find(int $id): ?Notification;

    /**
     * @return Notification[]
     */
    public function findByEmploye(Employe $employe): array;

    public function countNonLuesPourEmploye(Employe $employe): int;

    public function marquerToutesLues(Employe $employe): void;
}
