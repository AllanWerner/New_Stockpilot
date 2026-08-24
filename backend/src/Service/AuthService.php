<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\TokenDto;
use App\Entity\Employe;
use App\Repository\EmployeRepositoryInterface;
use App\Security\Exception\InvalidCredentialsException;
use App\Security\Exception\TooManyLoginAttemptsException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class AuthService
{
    public function __construct(
        private readonly EmployeRepositoryInterface $employeRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'limiter.login_attempts')]
        private readonly RateLimiterFactory $loginAttemptsLimiter,
    ) {
    }

    public function login(string $email, string $motDePasse): TokenDto
    {
        $limiter = $this->loginAttemptsLimiter->create($this->limiterKey($email));
        $reservation = $limiter->consume(1);

        if (!$reservation->isAccepted()) {
            throw new TooManyLoginAttemptsException(max(1, $reservation->getRetryAfter()->getTimestamp() - time()));
        }

        $employe = $this->verifierIdentifiants($email, $motDePasse);

        return new TokenDto(
            token: $this->genererToken($employe),
            idEmploye: $employe->getId(),
            nom: $employe->getNom(),
            prenom: $employe->getPrenom(),
            role: $employe->getRole()->value,
        );
    }

    private function verifierIdentifiants(string $email, string $motDePasse): Employe
    {
        $employe = $this->employeRepository->findByEmail($email);

        if (null === $employe || !$this->passwordHasher->isPasswordValid($employe, $motDePasse)) {
            throw new InvalidCredentialsException();
        }

        return $employe;
    }

    private function genererToken(Employe $employe): string
    {
        return $this->jwtTokenManager->create($employe);
    }

    private function limiterKey(string $email): string
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';

        return sprintf('%s|%s', strtolower($email), $ip);
    }
}
