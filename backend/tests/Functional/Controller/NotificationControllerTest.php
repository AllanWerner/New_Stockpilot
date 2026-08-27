<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Entity\Produit;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class NotificationControllerTest extends WebTestCase
{
    /**
     * @return array{token: string, idBoutique: int, idProduit: int}
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

        $produit = new Produit('Farine T65', '1.20', 'kg', $categorie, '9'.random_int(1000000, 9999999));
        $em->persist($produit);
        $em->flush();

        $stock = new Stock($produit, $boutique, seuilReappro: 3);
        $stock->setQuantiteActuelle(6);
        $em->persist($stock);
        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $emailGerant,
            'motDePasse' => 'password123',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        return ['token' => $token, 'idBoutique' => $boutique->getId(), 'idProduit' => $produit->getId()];
    }

    public function testAjustementFranchissantLeSeuilCreeUneNotification(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.notif-ajustement@stockpilot.test');

        $client->request('POST', "/api/produits/{$s['idProduit']}/ajustement", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode(['idBoutique' => $s['idBoutique'], 'quantite' => -4]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(2, $data['stock']['quantiteActuelle']);

        $client->request('GET', '/api/notifications', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);

        self::assertResponseIsSuccessful();
        $notifications = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $notifications);
        self::assertSame('SEUIL_CRITIQUE', $notifications[0]['type']);
        self::assertFalse($notifications[0]['lu']);
    }

    public function testMarquerLuePuisToutMarquerLu(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.notif-lecture@stockpilot.test');

        $client->request('POST', "/api/produits/{$s['idProduit']}/ajustement", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode(['idBoutique' => $s['idBoutique'], 'quantite' => -5]));

        $client->request('GET', '/api/notifications/non-lues/compte', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);
        self::assertSame(1, json_decode($client->getResponse()->getContent(), true)['compte']);

        $client->request('GET', '/api/notifications', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);
        $idNotification = json_decode($client->getResponse()->getContent(), true)[0]['idNotification'];

        $client->request('POST', "/api/notifications/{$idNotification}/lue", server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);
        self::assertResponseIsSuccessful();
        self::assertTrue(json_decode($client->getResponse()->getContent(), true)['lu']);

        $client->request('GET', '/api/notifications/non-lues/compte', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);
        self::assertSame(0, json_decode($client->getResponse()->getContent(), true)['compte']);
    }

    public function testAjustementSansFranchirLeSeuilNeCreePasDeNotification(): void
    {
        $client = static::createClient();
        $s = $this->scenario($client, 'gerant.notif-sans-alerte@stockpilot.test');

        $client->request('POST', "/api/produits/{$s['idProduit']}/ajustement", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$s['token'],
        ], content: json_encode(['idBoutique' => $s['idBoutique'], 'quantite' => -1]));

        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/notifications', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$s['token']]);
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }
}
