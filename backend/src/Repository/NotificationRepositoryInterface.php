<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;
}
