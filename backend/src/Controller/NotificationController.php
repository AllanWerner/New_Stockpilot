<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Notification;
use App\Repository\NotificationRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(private readonly NotificationRepositoryInterface $notificationRepository)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();

        return $this->json(array_map(
            fn (Notification $n) => $this->notificationVersReponse($n),
            $this->notificationRepository->findByEmploye($employe),
        ));
    }

    #[Route('/non-lues/compte', methods: ['GET'])]
    public function compteNonLues(): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();

        return $this->json(['compte' => $this->notificationRepository->countNonLuesPourEmploye($employe)]);
    }

    #[Route('/{id}/lue', methods: ['POST'])]
    public function marquerLue(int $id): JsonResponse
    {
        $notification = $this->trouverPourEmployeCourant($id);

        if (!$notification->isLu()) {
            $notification->marquerLue();
            $this->notificationRepository->save($notification);
        }

        return $this->json($this->notificationVersReponse($notification));
    }

    #[Route('/tout-marquer-lu', methods: ['POST'])]
    public function toutMarquerLu(): JsonResponse
    {
        /** @var Employe $employe */
        $employe = $this->getUser();
        $this->notificationRepository->marquerToutesLues($employe);

        return $this->json(['compte' => 0]);
    }

    private function trouverPourEmployeCourant(int $id): Notification
    {
        /** @var Employe $employe */
        $employe = $this->getUser();
        $notification = $this->notificationRepository->find($id);

        if (null === $notification || $notification->getEmploye()->getId() !== $employe->getId()) {
            throw new NotFoundHttpException('Notification introuvable.');
        }

        return $notification;
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationVersReponse(Notification $notification): array
    {
        return [
            'idNotification' => $notification->getId(),
            'type' => $notification->getType(),
            'message' => $notification->getMessage(),
            'lu' => $notification->isLu(),
            'dateCreation' => $notification->getDateCreation()->format(\DateTimeInterface::ATOM),
        ];
    }
}
