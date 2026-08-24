<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Employe;
use App\Entity\Enum\PosteEmploye;
use App\Entity\Enum\RoleEmploye;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $gerant = new Employe('Werner', 'Allan', 'gerant@stockpilot.test', RoleEmploye::GERANT);
        $gerant->setMotDePasse($this->passwordHasher->hashPassword($gerant, 'password123'));
        $manager->persist($gerant);

        $employe = new Employe('Martin', 'Léa', 'employe@stockpilot.test', RoleEmploye::EMPLOYE);
        $employe->setMotDePasse($this->passwordHasher->hashPassword($employe, 'password123'));
        $manager->persist($employe);

        $boutique = new Boutique('StockPilot Centre-ville', '12 rue de la République', 'Lyon');
        $manager->persist($boutique);

        $manager->persist(new Affectation($employe, $boutique, PosteEmploye::VENDEUR));

        $categorie = new Categorie('Épicerie');
        $manager->persist($categorie);

        $manager->flush();
    }
}
