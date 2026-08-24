<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Boutique;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Entity\Enum\TypeMouvement;
use App\Entity\MouvementStock;
use App\Entity\Produit;
use App\Entity\Stock;
use App\Repository\EmployeRepositoryInterface;
use App\Repository\MouvementStockRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;
use App\Repository\StockRepositoryInterface;
use App\Service\Exception\StockInsuffisantException;
use App\Service\MouvementStockService;
use App\Service\NotificationService;
use App\Service\StockService;
use PHPUnit\Framework\TestCase;

final class StockServiceTest extends TestCase
{
    private function produit(): Produit
    {
        $categorie = new \App\Entity\Categorie('Épicerie');

        return new Produit('Farine T65', '1.20', 'kg', $categorie, '1234567890123');
    }

    private function boutique(): Boutique
    {
        return new Boutique('Centre-ville', '1 rue Test', 'Lyon');
    }

    private function employe(): Employe
    {
        return new Employe('Werner', 'Allan', 'gerant@stockpilot.test', RoleEmploye::GERANT);
    }

    /**
     * MouvementStockService and NotificationService are thin, final wrappers —
     * real instances backed by mocked repositories, so the unit boundary stays
     * at the repository interfaces.
     */
    private function stockService(
        StockRepositoryInterface $stockRepository,
        MouvementStockRepositoryInterface $mouvementStockRepository,
        ?NotificationRepositoryInterface $notificationRepository = null,
        ?EmployeRepositoryInterface $employeRepository = null,
    ): StockService {
        $notificationService = new NotificationService(
            $notificationRepository ?? $this->createStub(NotificationRepositoryInterface::class),
            $employeRepository ?? $this->createStub(EmployeRepositoryInterface::class),
        );

        return new StockService($stockRepository, new MouvementStockService($mouvementStockRepository), $notificationService);
    }

    public function testIncrementerStockCreeLeStockSiAbsentEtEnregistreLeMouvement(): void
    {
        $produit = $this->produit();
        $boutique = $this->boutique();
        $employe = $this->employe();

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->method('findOneByProduitAndBoutique')->willReturn(null);
        $stockRepository->expects($this->once())->method('save');

        $mouvementStockRepository = $this->createMock(MouvementStockRepositoryInterface::class);
        $mouvementStockRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (MouvementStock $m) => TypeMouvement::RECEPTION === $m->getType()
                    && 10 === $m->getQuantite()
                    && $m->getProduit() === $produit
                    && $m->getBoutique() === $boutique
                    && $m->getEmploye() === $employe,
            ));

        $stockService = $this->stockService($stockRepository, $mouvementStockRepository);
        $stockService->incrementerStock($produit, $boutique, 10, TypeMouvement::RECEPTION, $employe);
    }

    public function testDecrementerStockRefuseSiQuantiteInsuffisante(): void
    {
        $produit = $this->produit();
        $boutique = $this->boutique();
        $employe = $this->employe();

        $stock = new Stock($produit, $boutique, seuilReappro: 5);
        $stock->setQuantiteActuelle(3);

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->method('findOneByProduitAndBoutique')->willReturn($stock);
        $stockRepository->expects($this->never())->method('save');

        $mouvementStockRepository = $this->createMock(MouvementStockRepositoryInterface::class);
        $mouvementStockRepository->expects($this->never())->method('save');

        $stockService = $this->stockService($stockRepository, $mouvementStockRepository);

        $this->expectException(StockInsuffisantException::class);
        $stockService->decrementerStock($produit, $boutique, 10, TypeMouvement::VENTE, $employe);
    }

    public function testDecrementerStockAccepteQuantiteSuffisante(): void
    {
        $produit = $this->produit();
        $boutique = $this->boutique();
        $employe = $this->employe();

        $stock = new Stock($produit, $boutique);
        $stock->setQuantiteActuelle(10);

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->method('findOneByProduitAndBoutique')->willReturn($stock);

        $mouvementStockRepository = $this->createMock(MouvementStockRepositoryInterface::class);
        $mouvementStockRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (MouvementStock $m) => -4 === $m->getQuantite()));

        $stockService = $this->stockService($stockRepository, $mouvementStockRepository);
        $stockService->decrementerStock($produit, $boutique, 4, TypeMouvement::VENTE, $employe);

        $this->assertSame(6, $stock->getQuantiteActuelle());
    }

    public function testDecrementerStockAlerteLorsDuFranchissementDuSeuil(): void
    {
        $produit = $this->produit();
        $boutique = $this->boutique();
        $employe = $this->employe();

        $stock = new Stock($produit, $boutique, seuilReappro: 5);
        $stock->setQuantiteActuelle(6);

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->method('findOneByProduitAndBoutique')->willReturn($stock);

        $gerant = $this->employe();
        $employeRepository = $this->createStub(EmployeRepositoryInterface::class);
        $employeRepository->method('findGerants')->willReturn([$gerant]);

        $notificationRepository = $this->createMock(NotificationRepositoryInterface::class);
        $notificationRepository->expects($this->once())->method('save');

        $stockService = $this->stockService(
            $stockRepository,
            $this->createMock(MouvementStockRepositoryInterface::class),
            $notificationRepository,
            $employeRepository,
        );

        $stockService->decrementerStock($produit, $boutique, 2, TypeMouvement::VENTE, $employe);

        $this->assertSame(4, $stock->getQuantiteActuelle());
    }

    public function testDecrementerStockNAlertePasSiDejaSousLeSeuil(): void
    {
        $produit = $this->produit();
        $boutique = $this->boutique();
        $employe = $this->employe();

        $stock = new Stock($produit, $boutique, seuilReappro: 5);
        $stock->setQuantiteActuelle(3);

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->method('findOneByProduitAndBoutique')->willReturn($stock);

        $notificationRepository = $this->createMock(NotificationRepositoryInterface::class);
        $notificationRepository->expects($this->never())->method('save');

        $stockService = $this->stockService(
            $stockRepository,
            $this->createMock(MouvementStockRepositoryInterface::class),
            $notificationRepository,
        );

        $stockService->decrementerStock($produit, $boutique, 1, TypeMouvement::VENTE, $employe);

        $this->assertSame(2, $stock->getQuantiteActuelle());
    }

    public function testListerSousSeuilDelegueAuRepository(): void
    {
        $boutique = $this->boutique();
        $stocks = [new Stock($this->produit(), $boutique)];

        $stockRepository = $this->createMock(StockRepositoryInterface::class);
        $stockRepository->expects($this->once())
            ->method('findSousSeuil')
            ->with($boutique)
            ->willReturn($stocks);

        $stockService = $this->stockService($stockRepository, $this->createMock(MouvementStockRepositoryInterface::class));

        $this->assertSame($stocks, $stockService->listerSousSeuil($boutique));
    }
}
