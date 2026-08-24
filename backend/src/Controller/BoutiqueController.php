<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\AffecterEmployeRequestDto;
use App\Dto\Request\CreateBoutiqueRequestDto;
use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Enum\PosteEmploye;
use App\Repository\AffectationRepositoryInterface;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\EmployeRepositoryInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * No dedicated BoutiqueService exists in the Jalon 4 class diagram (only
 * AccessVoter is wired to this controller) — persistence is handled directly
 * against the repository interfaces here, matching the approved design.
 */
#[Route('/api/boutiques')]
#[IsGranted('ROLE_GERANT')]
final class BoutiqueController extends AbstractController
{
    public function __construct(
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
        private readonly EmployeRepositoryInterface $employeRepository,
        private readonly AffectationRepositoryInterface $affectationRepository,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateBoutiqueRequestDto $dto): JsonResponse
    {
        $boutique = new Boutique($dto->nom, $dto->adresse, $dto->ville);
        $this->boutiqueRepository->save($boutique);

        return $this->json([
            'idBoutique' => $boutique->getId(),
            'nom' => $boutique->getNom(),
            'adresse' => $boutique->getAdresse(),
            'ville' => $boutique->getVille(),
        ], 201);
    }

    #[Route('/{id}/affecter', methods: ['POST'])]
    public function affecterEmploye(
        #[MapEntity(id: 'id')] Boutique $boutique,
        #[MapRequestPayload] AffecterEmployeRequestDto $dto,
    ): JsonResponse {
        $employe = $this->employeRepository->find($dto->idEmploye);

        if (null === $employe) {
            throw new NotFoundHttpException('Employé introuvable.');
        }

        $poste = PosteEmploye::from($dto->posteEmploye);
        $affectation = $this->affectationRepository->findOneByEmployeAndBoutique($employe, $boutique);

        if (null === $affectation) {
            $affectation = new Affectation($employe, $boutique, $poste);
        } else {
            $affectation->setPosteEmploye($poste);
        }

        $this->affectationRepository->save($affectation);

        return $this->json([
            'idEmploye' => $employe->getId(),
            'idBoutique' => $boutique->getId(),
            'poste' => $poste->value,
        ], 200);
    }
}
