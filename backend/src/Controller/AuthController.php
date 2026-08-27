<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\LoginRequestDto;
use App\Entity\Employe;
use App\Repository\AffectationRepositoryInterface;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AffectationRepositoryInterface $affectationRepository,
    ) {
    }

    #[Route('/login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequestDto $dto): JsonResponse
    {
        $token = $this->authService->login($dto->email, $dto->motDePasse);

        return $this->json($token);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();

        $affectations = array_map(
            static fn ($affectation) => [
                'idBoutique' => $affectation->getBoutique()->getId(),
                'nomBoutique' => $affectation->getBoutique()->getNom(),
                'poste' => $affectation->getPosteEmploye()->value,
            ],
            $this->affectationRepository->findByEmploye($employe),
        );

        return $this->json([
            'idEmploye' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'role' => $employe->getRole()->value,
            'boutiques' => $affectations,
        ]);
    }
}
