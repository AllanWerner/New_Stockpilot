<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Boutique;
use App\Entity\Employe;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Symfony-idiomatic wrapper around AccessVoter, so controllers can use
 * #[IsGranted('BOUTIQUE_ACCESS', subject: 'boutique')] / denyAccessUnlessGranted().
 *
 * @extends Voter<string, Boutique>
 */
final class BoutiqueVoter extends Voter
{
    public const string ACCESS = 'BOUTIQUE_ACCESS';
    public const string MANAGE = 'BOUTIQUE_MANAGE';

    public function __construct(private readonly AccessVoter $accessVoter)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::ACCESS, self::MANAGE], true) && $subject instanceof Boutique;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $employe = $token->getUser();

        if (!$employe instanceof Employe) {
            return false;
        }

        return match ($attribute) {
            self::ACCESS => $this->accessVoter->peutAccederBoutique($employe, $subject),
            self::MANAGE => $this->accessVoter->aDroitsElargis($employe, $subject),
            default => false,
        };
    }
}
