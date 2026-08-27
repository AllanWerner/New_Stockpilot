<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Employe;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Rejects requests from a deactivated employee even with an already-issued,
 * still-valid JWT — the JWT authenticator re-loads the Employe from the
 * database on every request, so this takes effect immediately rather than
 * waiting for the token to expire. Login itself is on a separate firewall
 * (see security.yaml) and checks `actif` directly in AuthService instead,
 * since it never goes through this authenticator pipeline.
 */
final class EmployeUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof Employe && !$user->isActif()) {
            throw new DisabledException('Ce compte a été désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
