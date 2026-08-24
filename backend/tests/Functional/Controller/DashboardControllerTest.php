<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Entity\Enum\StatutCommande;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardControllerTest extends WebTestCase
{
    /**
     * @return array{token: string, idBoutique: int}
     */
    private function scenario(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $emailGerant): array
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $gerant = new Employe('Werner', 'Allan', $emailGerant, RoleEmploye::GERANT);
        $gerant->setMotDePasse($hasher->hashPassword($gerant, 'password123'));
        $em->persist($gerant);

        $boutique = new Boutique('Centre-ville', '1 rue Test', 'Lyon');
        $em->persist($boutique);

        $categorie = new Categorie('Épicerie '.uniqid());
        $em->persist($categorie);

        $produitRupture = new Produit('Farine T65', '2.00', 'kg', $categorie, '1'.random_int(1000000, 9999999));
        $produitCritique = new Produit('Café en grain', '5.00', 'kg', $categorie, '2'.random_int(1000000, 9999999));
        $produitOk = new Produit('Sel fin', '1.00', 'kg', $categorie, '3'.random_int(1000000, 9999999));
        $em->persist($produitRupture);
        $em->persist($produitCritique);
        $em->persist($produitOk);
        $em->flush();

        $stockRupture = new Stock($produitRupture, $boutique, seuilReappro: 10, quantiteCommandeReco: 20);
        $stockRupture->setQuantiteActuelle(0);

        $stockCritique = new Stock($produitCritique, $boutique, seuilReappro: 10, quantiteCommandeReco: 20);
        $stockCritique->setQuantiteActuelle(4);

        $stockOk = new Stock($produitOk, $boutique, seuilReappro: 5, quantiteCommandeReco: 10);
        $stockOk->setQuantiteActuelle(50);

        $em->persist($stockRupture);
        $em->persist($stockCritique);
        $em->persist($stockOk);

        $fournisseur = new Fournisseur('Grossiste Test');
        $em->persist($fournisseur);

        $commande = new Commande($boutique, $fournisseur, $gerant);
        $commande->setStatut(StatutCommande::ENVOYEE);
        $em->persist($commande);

        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $emailGerant,
            'motDePasse' => 'password123',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        return ['token' => $token, 'idBoutique' => $boutique->getId()];
    }

    public function testTableauDeBordCalculeLesIndicateursCles(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.dashboard@stockpilot.test');

        $client->request('GET', '/api/dashboard', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('70.00', $data['valeurStock']);
        self::assertSame(1, $data['referencesEnRupture']);
        self::assertSame(1, $data['sousSeuilCritique']);
        self::assertSame(1, $data['commandesEnCours']);

        self::assertCount(2, $data['produitsSousSeuilCritique']);
        self::assertSame('rupture', $data['produitsSousSeuilCritique'][0]['statut']);
        self::assertSame('critique', $data['produitsSousSeuilCritique'][1]['statut']);

        self::assertCount(14, $data['evolutionValorisation']);
        self::assertSame('70.00', $data['evolutionValorisation'][13]['valeur']);
    }

    public function testFiltreParBoutiqueInaccessibleRenvoie403(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.dashboard-filtre@stockpilot.test');

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $autreBoutique = new Boutique('Rive gauche', '2 rue Test', 'Lyon');
        $em->persist($autreBoutique);

        $employe = new Employe('Test', 'Vendeur', 'employe.dashboard-filtre@stockpilot.test', RoleEmploye::EMPLOYE);
        $employe->setMotDePasse($hasher->hashPassword($employe, 'password123'));
        $em->persist($employe);
        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'employe.dashboard-filtre@stockpilot.test',
            'motDePasse' => 'password123',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('GET', '/api/dashboard?idBoutique='.$s['idBoutique'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
