<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerTest extends WebTestCase
{
    private function creerEmploye(EntityManagerInterface $em, UserPasswordHasherInterface $hasher, string $email, string $motDePasse): Employe
    {
        $employe = new Employe('Test', 'Utilisateur', $email, RoleEmploye::GERANT);
        $employe->setMotDePasse($hasher->hashPassword($employe, $motDePasse));
        $em->persist($employe);
        $em->flush();

        return $employe;
    }

    public function testLoginReussitEtRenvoieUnToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->creerEmploye(
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class),
            'gerant.login.ok@stockpilot.test',
            'password123',
        );

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'gerant.login.ok@stockpilot.test',
            'motDePasse' => 'password123',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertSame('GERANT', $data['role']);
    }

    public function testLoginRefuseAvecMotDePasseInvalide(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->creerEmploye(
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class),
            'gerant.login.ko@stockpilot.test',
            'password123',
        );

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'gerant.login.ko@stockpilot.test',
            'motDePasse' => 'mauvais-mot-de-passe',
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginEstLimiteApresTropDeTentatives(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->creerEmploye(
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class),
            'gerant.rate-limit@stockpilot.test',
            'password123',
        );

        for ($i = 0; $i < 5; ++$i) {
            $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
                'email' => 'gerant.rate-limit@stockpilot.test',
                'motDePasse' => 'mauvais-mot-de-passe',
            ]));
        }

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'gerant.rate-limit@stockpilot.test',
            'motDePasse' => 'mauvais-mot-de-passe',
        ]));

        self::assertResponseStatusCodeSame(429);
    }

    public function testMeSansTokenRenvoie401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeAvecTokenRenvoieLeProfil(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->creerEmploye(
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class),
            'gerant.me@stockpilot.test',
            'password123',
        );

        $client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'gerant.me@stockpilot.test',
            'motDePasse' => 'password123',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('GET', '/api/auth/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('gerant.me@stockpilot.test', $data['email']);
    }
}
