<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\AffecterEmployeRequestDto;
use App\Dto\Request\CreateBoutiqueRequestDto;
use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Employe;
use App\Entity\Enum\PosteEmploye;
use App\Repository\AffectationRepositoryInterface;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\EmployeRepositoryInterface;
use App\Security\AccessVoter;
use App\Service\Exception\SuppressionImpossibleException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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
final class BoutiqueController extends AbstractController
{
    public function __construct(
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
        private readonly EmployeRepositoryInterface $employeRepository,
        private readonly AffectationRepositoryInterface $affectationRepository,
        private readonly AccessVoter $accessVoter,
    ) {
    }

    /**
     * Boutiques the current employee can act on: every boutique for a gérant
     * (full access), or just their Affectation rows for an employé — powers
     * the persistent "Ma boutique" selector in the frontend header. Includes
     * inactive boutiques too — the frontend selector filters those out
     * client-side, but the "Gérer l'organisation" admin page needs to see
     * (and reactivate) them.
     */
    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();

        $boutiques = $this->accessVoter->estGerant($employe)
            ? $this->boutiqueRepository->findAll()
            : $this->boutiqueRepository->findByEmploye($employe);

        return $this->json(array_map(fn (Boutique $b) => $this->boutiqueVersReponse($b), $boutiques));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function create(#[MapRequestPayload] CreateBoutiqueRequestDto $dto): JsonResponse
    {
        $boutique = new Boutique($dto->nom, $dto->adresse, $dto->ville);
        $this->boutiqueRepository->save($boutique);

        return $this->json($this->boutiqueVersReponse($boutique), 201);
    }

    #[Route('/{id}/affecter', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
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

    #[Route('/{id}/activer', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function activer(#[MapEntity(id: 'id')] Boutique $boutique): JsonResponse
    {
        $boutique->setActif(true);
        $this->boutiqueRepository->save($boutique);

        return $this->json($this->boutiqueVersReponse($boutique));
    }

    #[Route('/{id}/desactiver', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function desactiver(#[MapEntity(id: 'id')] Boutique $boutique): JsonResponse
    {
        $boutique->setActif(false);
        $this->boutiqueRepository->save($boutique);

        return $this->json($this->boutiqueVersReponse($boutique));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_GERANT')]
    public function supprimer(#[MapEntity(id: 'id')] Boutique $boutique): JsonResponse
    {
        try {
            $this->boutiqueRepository->delete($boutique);
        } catch (ForeignKeyConstraintViolationException) {
            throw new SuppressionImpossibleException('Impossible de supprimer : cette boutique a des commandes ou mouvements de stock enregistrés. Désactivez-la plutôt.');
        }

        return $this->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function boutiqueVersReponse(Boutique $boutique): array
    {
        return [
            'idBoutique' => $boutique->getId(),
            'nom' => $boutique->getNom(),
            'adresse' => $boutique->getAdresse(),
            'ville' => $boutique->getVille(),
            'actif' => $boutique->isActif(),
        ];
    }
}
