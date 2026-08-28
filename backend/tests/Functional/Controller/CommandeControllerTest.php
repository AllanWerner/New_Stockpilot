<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CommandeControllerTest extends WebTestCase
{
    use MailerAssertionsTrait;

    /**
     * @return array{token: string, idBoutique: int, idFournisseur: int, idProduit: int}
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

        $fournisseur = new Fournisseur('Grossiste Nord-Est');
        $fournisseur->setEmailContact('contact@grossiste-ne.test');
        $em->persist($fournisseur);

        $categorie = new Categorie('Épicerie '.uniqid());
        $em->persist($categorie);

        $produit = new Produit('Farine T65', '1.20', 'kg', $categorie, '11111'.random_int(100000, 999999));
        $em->persist($produit);

        $em->flush();

        $stock = new Stock($produit, $boutique, seuilReappro: 10, quantiteCommandeReco: 20);
        $stock->setQuantiteActuelle(3);
        $em->persist($stock);
        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $emailGerant,
            'motDePasse' => 'password123',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        return [
            'token' => $token,
            'idBoutique' => $boutique->getId(),
            'idFournisseur' => $fournisseur->getId(),
            'idProduit' => $produit->getId(),
        ];
    }

    public function testProduitsSousSeuilRenvoieLeProduitSousStock(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.sous-seuil@stockpilot.test');

        $client->request('GET', "/api/boutiques/{$s['idBoutique']}/produits-sous-seuil", server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame($s['idProduit'], $data[0]['idProduit']);
        self::assertSame(20, $data[0]['quantiteRecommandee']);
    }

    public function testCreationEnvoieLaCommandeEtUnEmail(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.creation-commande@stockpilot.test');

        $client->request('POST', '/api/commandes', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'idBoutique' => $s['idBoutique'],
            'idFournisseur' => $s['idFournisseur'],
            'lignes' => [
                ['idProduit' => $s['idProduit'], 'quantiteCommandee' => 20, 'prixUnitaire' => '1.20'],
            ],
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('ENVOYEE', $data['statut']);
        self::assertCount(1, $data['lignes']);
        self::assertSame('24.00', $data['lignes'][0]['sousTotal']);
        self::assertEmailCount(1);
    }

    public function testReceptionCompleteMetLeStockAJourEtPasseLaCommandeARecue(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.reception-complete@stockpilot.test');

        $client->request('POST', '/api/commandes', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'idBoutique' => $s['idBoutique'],
            'idFournisseur' => $s['idFournisseur'],
            'lignes' => [
                ['idProduit' => $s['idProduit'], 'quantiteCommandee' => 20, 'prixUnitaire' => '1.20'],
            ],
        ]));
        $commande = json_decode($client->getResponse()->getContent(), true);
        $idLigne = $commande['lignes'][0]['idLigneCommande'];

        $client->request('POST', "/api/commandes/{$commande['idCommande']}/reception", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'lignes' => [['idLigneCommande' => $idLigne, 'quantiteRecue' => 20]],
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('RECUE', $data['statut']);
        self::assertSame(20, $data['lignes'][0]['quantiteRecue']);
    }

    public function testReceptionPartielleDonneLeStatutRecuePartielle(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.reception-partielle@stockpilot.test');

        $client->request('POST', '/api/commandes', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'idBoutique' => $s['idBoutique'],
            'idFournisseur' => $s['idFournisseur'],
            'lignes' => [
                ['idProduit' => $s['idProduit'], 'quantiteCommandee' => 20, 'prixUnitaire' => '1.20'],
            ],
        ]));
        $commande = json_decode($client->getResponse()->getContent(), true);
        $idLigne = $commande['lignes'][0]['idLigneCommande'];

        $client->request('POST', "/api/commandes/{$commande['idCommande']}/reception", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'lignes' => [['idLigneCommande' => $idLigne, 'quantiteRecue' => 12]],
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('RECUE_PARTIELLE', $data['statut']);
    }

    public function testReceptionAvecQuantiteSuperieureALaCommandeRenvoie422(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.reception-trop@stockpilot.test');

        $client->request('POST', '/api/commandes', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'idBoutique' => $s['idBoutique'],
            'idFournisseur' => $s['idFournisseur'],
            'lignes' => [
                ['idProduit' => $s['idProduit'], 'quantiteCommandee' => 20, 'prixUnitaire' => '1.20'],
            ],
        ]));
        $commande = json_decode($client->getResponse()->getContent(), true);
        $idLigne = $commande['lignes'][0]['idLigneCommande'];

        $client->request('POST', "/api/commandes/{$commande['idCommande']}/reception", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode([
            'lignes' => [['idLigneCommande' => $idLigne, 'quantiteRecue' => 999]],
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testListeSansIdBoutiqueRenvoie400(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.liste-sans-boutique@stockpilot.test');

        $client->request('GET', '/api/commandes', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);

        self::assertResponseStatusCodeSame(400);
    }
}
