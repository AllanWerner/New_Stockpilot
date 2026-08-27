<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class BoutiqueControllerTest extends WebTestCase
{
    private function token(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $email, RoleEmploye $role): string
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $employe = new Employe('Test', 'Utilisateur', $email, $role);
        $employe->setMotDePasse($hasher->hashPassword($employe, 'password123'));
        $em->persist($employe);
        $em->flush();

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'motDePasse' => 'password123',
        ]));

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testCreationBoutiqueParUnGerantReussie(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'gerant.boutique-create@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['nom' => 'Centre-ville', 'adresse' => '1 rue Test', 'ville' => 'Lyon']));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Centre-ville', $data['nom']);
    }

    public function testCreationBoutiqueParUnEmployeRefusee(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'employe.boutique-create@stockpilot.test', RoleEmploye::EMPLOYE);

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['nom' => 'Centre-ville', 'adresse' => '1 rue Test', 'ville' => 'Lyon']));

        self::assertResponseStatusCodeSame(403);
    }

    public function testAffecterEmployeAUneBoutique(): void
    {
        $client = static::createClient();
        $gerantToken = $this->token($client, 'gerant.affecter@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$gerantToken,
        ], content: json_encode(['nom' => 'Rive gauche', 'adresse' => '2 rue Test', 'ville' => 'Lyon']));
        $idBoutique = json_decode($client->getResponse()->getContent(), true)['idBoutique'];

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $employe = new Employe('Martin', 'Léa', 'vendeuse.affecter@stockpilot.test', RoleEmploye::EMPLOYE);
        $employe->setMotDePasse($hasher->hashPassword($employe, 'password123'));
        $em->persist($employe);
        $em->flush();

        $client->request('POST', "/api/boutiques/{$idBoutique}/affecter", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$gerantToken,
        ], content: json_encode(['idEmploye' => $employe->getId(), 'posteEmploye' => 'VENDEUR']));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('VENDEUR', $data['poste']);
    }

    public function testListePourUnGerantRenvoieToutesLesBoutiques(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'gerant.liste-boutiques@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['nom' => 'Nord', 'adresse' => '3 rue Test', 'ville' => 'Lyon']));

        $client->request('GET', '/api/boutiques', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($data);
        self::assertArrayHasKey('nom', $data[0]);
    }

    public function testListePourUnEmployeNeRenvoieQueSesBoutiquesAffectees(): void
    {
        $client = static::createClient();
        $gerantToken = $this->token($client, 'gerant.liste-scoped@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$gerantToken,
        ], content: json_encode(['nom' => 'Sud', 'adresse' => '4 rue Test', 'ville' => 'Lyon']));
        $idBoutiqueAffectee = json_decode($client->getResponse()->getContent(), true)['idBoutique'];

        $client->request('POST', '/api/boutiques', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$gerantToken,
        ], content: json_encode(['nom' => 'Est', 'adresse' => '5 rue Test', 'ville' => 'Lyon']));

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $employe = new Employe('Petit', 'Sam', 'employe.liste-scoped@stockpilot.test', RoleEmploye::EMPLOYE);
        $employe->setMotDePasse($hasher->hashPassword($employe, 'password123'));
        $em->persist($employe);
        $em->flush();

        $client->request('POST', "/api/boutiques/{$idBoutiqueAffectee}/affecter", server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$gerantToken,
        ], content: json_encode(['idEmploye' => $employe->getId(), 'posteEmploye' => 'VENDEUR']));

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'employe.liste-scoped@stockpilot.test',
            'motDePasse' => 'password123',
        ]));
        $employeToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('GET', '/api/boutiques', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$employeToken]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame((int) $idBoutiqueAffectee, $data[0]['idBoutique']);
    }
}
