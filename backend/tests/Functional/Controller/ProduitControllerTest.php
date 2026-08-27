<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Categorie;
use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use App\Tests\Fake\OffMockResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProduitControllerTest extends WebTestCase
{
    private function tokenGerant(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $email): string
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $employe = new Employe('Test', 'Gérant', $email, RoleEmploye::GERANT);
        $employe->setMotDePasse($hasher->hashPassword($employe, 'password123'));
        $em->persist($employe);
        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'motDePasse' => 'password123',
        ]));

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testListeSansTokenRenvoie401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/produits');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListeAvecTokenRenvoie200(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.liste@stockpilot.test');

        $client->request('GET', '/api/produits', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('items', $data);
    }

    public function testCreationManuelleAvecPayloadInvalideRenvoie422(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.creation-ko@stockpilot.test');

        $client->request('POST', '/api/produits', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode([
            'nom' => '',
            'prixAchat' => '4.50',
            'unite' => 'piece',
            'idCategorie' => 1,
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreationManuelleReussie(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.creation-ok@stockpilot.test');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $categorie = new Categorie('Épicerie test');
        $em->persist($categorie);
        $em->flush();

        $client->request('POST', '/api/produits', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode([
            'nom' => 'Café en grain 250g',
            'prixAchat' => '4.50',
            'unite' => 'piece',
            'idCategorie' => $categorie->getId(),
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Café en grain 250g', $data['nom']);
    }

    public function testScanAvecCodeBarreConnuCotéOffCreeLeProduitAutomatiquement(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.scan-auto@stockpilot.test');

        $client->request('POST', '/api/produits/scan', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['codeBarre' => OffMockResponseFactory::CODE_BARRE_CONNU]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('AUTOMATIQUE', $data['sourceCompletion']);
    }

    public function testScanAvecCodeBarreInconnuCotéOffCreeUnProduitACompleter(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.scan-manuel@stockpilot.test');

        $client->request('POST', '/api/produits/scan', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['codeBarre' => '0000000000000']));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('MANUELLE', $data['sourceCompletion']);
    }

    public function testScanDeuxFoisLeMemeCodeBarreRenvoie409(): void
    {
        $client = static::createClient();
        $token = $this->tokenGerant($client, 'gerant.scan-doublon@stockpilot.test');

        // Deliberately a barcode distinct from OffMockResponseFactory::CODE_BARRE_CONNU
        // (used by another test method) so the two tests can't collide on the
        // unique produit.code_barre constraint regardless of execution order.
        $payload = json_encode(['codeBarre' => '9999999999999']);
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token];

        $client->request('POST', '/api/produits/scan', server: $headers, content: $payload);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/produits/scan', server: $headers, content: $payload);
        self::assertResponseStatusCodeSame(409);
    }
}
