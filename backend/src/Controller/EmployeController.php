<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateEmployeRequestDto;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Repository\AffectationRepositoryInterface;
use App\Repository\EmployeRepositoryInterface;
use App\Service\Exception\EmployeDejaExistantException;
use App\Service\Exception\SuppressionImpossibleException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * No dedicated EmployeService exists, mirroring BoutiqueController — this is
 * gérant-only org-management CRUD, persisted directly against the repository
 * interfaces here rather than through a service layer.
 */
#[Route('/api/employes')]
final class EmployeController extends AbstractController
{
    public function __construct(
        private readonly EmployeRepositoryInterface $employeRepository,
        private readonly AffectationRepositoryInterface $affectationRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_GERANT')]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            fn (Employe $employe) => $this->employeVersReponse($employe),
            $this->employeRepository->findAll(),
        ));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function create(#[MapRequestPayload] CreateEmployeRequestDto $dto): JsonResponse
    {
        if (null !== $this->employeRepository->findByEmail($dto->email)) {
            throw new EmployeDejaExistantException();
        }

        $employe = new Employe($dto->nom, $dto->prenom, $dto->email, RoleEmploye::from($dto->role));
        $employe->setMotDePasse($this->passwordHasher->hashPassword($employe, $dto->motDePasse));
        $this->employeRepository->save($employe);

        return $this->json($this->employeVersReponse($employe), 201);
    }

    #[Route('/{id}/activer', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function activer(int $id): JsonResponse
    {
        $employe = $this->trouverEmploye($id);
        $employe->setActif(true);
        $this->employeRepository->save($employe);

        return $this->json($this->employeVersReponse($employe));
    }

    #[Route('/{id}/desactiver', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function desactiver(int $id): JsonResponse
    {
        $employe = $this->trouverEmploye($id);
        $this->interdireAutoModification($employe);

        $employe->setActif(false);
        $this->employeRepository->save($employe);

        return $this->json($this->employeVersReponse($employe));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_GERANT')]
    public function supprimer(int $id): JsonResponse
    {
        $employe = $this->trouverEmploye($id);
        $this->interdireAutoModification($employe);

        try {
            $this->employeRepository->delete($employe);
        } catch (ForeignKeyConstraintViolationException) {
            throw new SuppressionImpossibleException(
                'Impossible de supprimer : cet employé a des commandes ou mouvements de stock enregistrés. Désactivez-le plutôt.',
            );
        }

        return $this->json(null, 204);
    }

    private function trouverEmploye(int $id): Employe
    {
        $employe = $this->employeRepository->find($id);

        if (null === $employe) {
            throw new NotFoundHttpException('Employé introuvable.');
        }

        return $employe;
    }

    private function interdireAutoModification(Employe $employe): void
    {
        /** @var Employe $courant */
        $courant = $this->getUser();

        if ($courant->getId() === $employe->getId()) {
            throw new ConflictHttpException('Vous ne pouvez pas désactiver ou supprimer votre propre compte.');
        }
    }

    private function employeVersReponse(Employe $employe): array
    {
        $affectations = array_map(
            static fn ($affectation) => [
                'idBoutique' => $affectation->getBoutique()->getId(),
                'nomBoutique' => $affectation->getBoutique()->getNom(),
                'poste' => $affectation->getPosteEmploye()->value,
            ],
            $this->affectationRepository->findByEmploye($employe),
        );

        return [
            'idEmploye' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'role' => $employe->getRole()->value,
            'actif' => $employe->isActif(),
            'boutiques' => $affectations,
        ];
    }
}
