<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employe;
use App\Repository\BoutiqueRepositoryInterface;
use App\Security\BoutiqueVoter;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
    ) {
    }

    #[Route('/api/dashboard', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();

        $idBoutique = $request->query->get('idBoutique');
        $boutiqueFiltre = null;

        if (null !== $idBoutique) {
            $boutiqueFiltre = $this->boutiqueRepository->find((int) $idBoutique);

            if (null === $boutiqueFiltre) {
                throw new NotFoundHttpException('Boutique introuvable.');
            }

            $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutiqueFiltre);
        }

        return $this->json($this->dashboardService->consulterTableauDeBord($employe, $boutiqueFiltre));
    }
}
