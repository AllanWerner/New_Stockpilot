<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class FournisseurControllerTest extends WebTestCase
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

    public function testCreationParUnGerantReussie(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'gerant.fournisseur-create@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/fournisseurs', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode([
            'nom' => 'Grossiste Nord-Est',
            'emailContact' => 'contact@grossiste-ne.test',
            'telephone' => '0102030405',
            'delaiLivraisonJours' => 5,
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Grossiste Nord-Est', $data['nom']);
    }

    public function testCreationParUnEmployeRefusee(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'employe.fournisseur-create@stockpilot.test', RoleEmploye::EMPLOYE);

        $client->request('POST', '/api/fournisseurs', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['nom' => 'Grossiste Test']));

        self::assertResponseStatusCodeSame(403);
    }

    public function testListeAccessibleATousLesEmployes(): void
    {
        $client = static::createClient();
        $token = $this->token($client, 'gerant.fournisseur-list@stockpilot.test', RoleEmploye::GERANT);

        $client->request('POST', '/api/fournisseurs', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], content: json_encode(['nom' => 'Grossiste Sud']));

        $client->request('GET', '/api/fournisseurs', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($data);
    }
}
