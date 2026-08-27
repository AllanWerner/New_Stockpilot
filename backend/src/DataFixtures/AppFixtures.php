<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\PosteEmploye;
use App\Entity\Enum\RoleEmploye;
use App\Entity\Enum\StatutCommande;
use App\Entity\Enum\TypeMouvement;
use App\Entity\Fournisseur;
use App\Entity\LigneCommande;
use App\Entity\MouvementStock;
use App\Entity\Notification;
use App\Entity\Produit;
use App\Entity\Proposer;
use App\Entity\Stock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Seed data for local manual testing (see the F1-F5 manual test checklist).
 * Load with: docker compose exec php php bin/console doctrine:fixtures:load -n.
 *
 * Builds a realistic 3-boutique dataset that deliberately covers every
 * role/périmètre combination and every stock status (rupture/critique/ok)
 * the CDCF's functional checklist exercises, plus a few historical
 * commandes/mouvements/notifications so screens aren't empty on first login.
 * Some seeded states are intentionally left "ready" for a manual action
 * (see the checklist) rather than pre-completed — e.g. Sucre en poudre at
 * Centre-ville stays above its seuil so a live ajustement can be used to
 * trigger a fresh seuil-critique alert.
 */
final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // ---- Employés (F1) ----
        $gerant = $this->employe($manager, 'Werner', 'Allan', 'gerant@stockpilot.test', RoleEmploye::GERANT);
        $responsableCentreVille = $this->employe($manager, 'Martin', 'Léa', 'responsable.centreville@stockpilot.test', RoleEmploye::EMPLOYE);
        $vendeurCentreVille = $this->employe($manager, 'Dubois', 'Karim', 'vendeur.centreville@stockpilot.test', RoleEmploye::EMPLOYE);
        $vendeurRiveGauche = $this->employe($manager, 'Petit', 'Sofia', 'vendeur.rivegauche@stockpilot.test', RoleEmploye::EMPLOYE);

        // ---- Boutiques (F1) ----
        $centreVille = new Boutique('StockPilot Centre-ville', '12 rue de la République', 'Lyon');
        $riveGauche = new Boutique('StockPilot Rive Gauche', '8 quai de la Rive Gauche', 'Lyon');
        $marcheCouvert = new Boutique('StockPilot Marché Couvert', 'Place du Marché', 'Lyon');
        $manager->persist($centreVille);
        $manager->persist($riveGauche);
        $manager->persist($marcheCouvert);

        // ---- Affectations (F1) — Marché Couvert reste sans affectation :
        // périmètre "gérant uniquement" pour tester l'accès refusé aux employés.
        $manager->persist(new Affectation($responsableCentreVille, $centreVille, PosteEmploye::RESPONSABLE));
        $manager->persist(new Affectation($vendeurCentreVille, $centreVille, PosteEmploye::VENDEUR));
        $manager->persist(new Affectation($vendeurRiveGauche, $riveGauche, PosteEmploye::VENDEUR));

        // ---- Catégories (F2) ----
        $epicerie = new Categorie('Épicerie');
        $boissons = new Categorie('Boissons');
        $hygiene = new Categorie('Hygiène & entretien');
        $papeterie = new Categorie('Papeterie');
        $manager->persist($epicerie);
        $manager->persist($boissons);
        $manager->persist($hygiene);
        $manager->persist($papeterie);

        // ---- Fournisseurs (F1/F3) ----
        $grossisteNordEst = $this->fournisseur($manager, 'Grossiste Nord-Est', 'contact@grossiste-nord-est.test', '0102030405', 5);
        $cooperativeBio = $this->fournisseur($manager, 'Coopérative Bio Locale', 'commandes@bio-locale.test', '0203040506', 3);
        $papeterieDiffusion = $this->fournisseur($manager, 'Papeterie Diffusion', 'ventes@papeterie-diffusion.test', '0304050607', 10);

        // ---- Produits (F2) — codes-barres fictifs (préfixe 340...), donc
        // sans collision avec Open Food Facts pour les tests de scan.
        $farine = new Produit('Farine T65 — 1kg', '1.20', 'kg', $epicerie, '3400000000011');
        $cafe = new Produit('Café en grain 250g', '4.50', 'piece', $epicerie, '3400000000028');
        $sucre = new Produit('Sucre en poudre 1kg', '1.80', 'piece', $epicerie, '3400000000035');
        $jus = new Produit("Jus d'orange 1L", '2.10', 'piece', $boissons, '3400000000042');
        $eau = new Produit('Eau minérale 6x1.5L', '3.40', 'pack', $boissons, '3400000000059');
        $savon = new Produit('Savon artisanal lavande', '2.80', 'piece', $hygiene, '3400000000066');
        $liquideVaisselle = new Produit('Liquide vaisselle 500ml', '1.95', 'piece', $hygiene, '3400000000073');
        $carnets = new Produit('Carnets recyclés A5', '3.10', 'piece', $papeterie, '3400000000080');
        $stylos = new Produit('Stylos bille x10', '2.50', 'pack', $papeterie, '3400000000097');

        foreach ([$farine, $cafe, $sucre, $jus, $eau, $savon, $liquideVaisselle, $carnets, $stylos] as $produit) {
            $manager->persist($produit);
        }

        // ---- Catalogue fournisseur (F1 "catalogue de produits proposés") ----
        $manager->persist(new Proposer($grossisteNordEst, $farine, '0.95', 'GNE-FAR-01'));
        $manager->persist(new Proposer($grossisteNordEst, $sucre, '1.40', 'GNE-SUC-01'));
        $manager->persist(new Proposer($grossisteNordEst, $cafe, '3.80', 'GNE-CAF-01'));
        $manager->persist(new Proposer($cooperativeBio, $savon, '2.10', 'CBL-SAV-01'));
        $manager->persist(new Proposer($cooperativeBio, $liquideVaisselle, '1.50', 'CBL-LIQ-01'));
        $manager->persist(new Proposer($papeterieDiffusion, $carnets, '2.20', 'PAP-CAR-01'));
        $manager->persist(new Proposer($papeterieDiffusion, $stylos, '1.70', 'PAP-STY-01'));

        // ---- Stocks (F2) — statuts volontairement variés par boutique ----
        // Centre-ville
        $this->stock($manager, $farine, $centreVille, 0, 10, 20); // rupture
        $this->stock($manager, $cafe, $centreVille, 9, 12, 24); // critique
        $this->stock($manager, $sucre, $centreVille, 40, 15, 30); // ok — cible du test d'ajustement en direct
        $this->stock($manager, $jus, $centreVille, 6, 15, 20); // critique
        $this->stock($manager, $eau, $centreVille, 25, 10, 15); // ok
        $this->stock($manager, $savon, $centreVille, 6, 15, 20); // critique
        $this->stock($manager, $liquideVaisselle, $centreVille, 18, 8, 12); // ok
        $this->stock($manager, $carnets, $centreVille, 0, 8, 15); // rupture
        $this->stock($manager, $stylos, $centreVille, 30, 10, 15); // ok

        // Rive Gauche
        $this->stock($manager, $farine, $riveGauche, 15, 8, 15); // ok
        $this->stock($manager, $savon, $riveGauche, 6, 15, 20); // critique
        $this->stock($manager, $jus, $riveGauche, 2, 10, 15); // critique
        $this->stock($manager, $sucre, $riveGauche, 0, 10, 15); // rupture

        // Marché Couvert (visible gérant uniquement)
        $this->stock($manager, $cafe, $marcheCouvert, 9, 12, 24); // critique
        $this->stock($manager, $eau, $marcheCouvert, 12, 10, 15); // ok
        $this->stock($manager, $stylos, $marcheCouvert, 5, 10, 15); // critique

        $manager->flush();

        // ---- Historique de mouvements (F4) — purement cosmétique pour que
        // le graphique d'évolution de la valorisation ne soit pas plat.
        $this->historiqueMouvements($manager, $centreVille, $gerant, [$farine, $cafe, $sucre, $savon]);

        // ---- Commandes fournisseurs (F3) ----
        $commandeRecue = $this->commandeRecue($manager, $centreVille, $grossisteNordEst, $gerant, $farine, $cafe);
        $this->commandePartielle($manager, $centreVille, $cooperativeBio, $gerant, $savon, $liquideVaisselle);
        $this->commandeEnvoyee($manager, $riveGauche, $grossisteNordEst, $gerant, $sucre, $farine);

        $manager->flush();

        // ---- Notifications (F5) — quelques-unes déjà lues pour tester
        // l'écran d'historique ; les alertes "fraîches" restent à déclencher
        // en direct via le checklist (ajustement, réception).
        $notifReception = new Notification(
            'RECEPTION_COMMANDE',
            sprintf('Commande #%d (%s) — reçue intégralement.', (int) $commandeRecue->getId(), $centreVille->getNom()),
            $gerant,
        );
        $notifReception->marquerLue();
        $manager->persist($notifReception);

        $notifSeuil = new Notification(
            'SEUIL_CRITIQUE',
            'Le produit "Carnets recyclés A5" (StockPilot Centre-ville) a atteint son seuil de réapprovisionnement : 0 unité(s) restante(s) (seuil : 8).',
            $gerant,
        );
        $notifSeuil->marquerLue();
        $manager->persist($notifSeuil);

        $manager->flush();
    }

    private function employe(ObjectManager $manager, string $nom, string $prenom, string $email, RoleEmploye $role): Employe
    {
        $employe = new Employe($nom, $prenom, $email, $role);
        $employe->setMotDePasse($this->passwordHasher->hashPassword($employe, 'password123'));
        $manager->persist($employe);

        return $employe;
    }

    private function fournisseur(
        ObjectManager $manager,
        string $nom,
        string $emailContact,
        string $telephone,
        int $delaiLivraisonJours,
    ): Fournisseur {
        $fournisseur = new Fournisseur($nom);
        $fournisseur->setEmailContact($emailContact);
        $fournisseur->setTelephone($telephone);
        $fournisseur->setDelaiLivraisonJours($delaiLivraisonJours);
        $manager->persist($fournisseur);

        return $fournisseur;
    }

    private function stock(
        ObjectManager $manager,
        Produit $produit,
        Boutique $boutique,
        int $quantiteActuelle,
        int $seuilReappro,
        int $quantiteCommandeReco,
    ): void {
        $stock = new Stock($produit, $boutique, $seuilReappro, $quantiteCommandeReco);
        $stock->setQuantiteActuelle($quantiteActuelle);
        $manager->persist($stock);
    }

    /**
     * @param Produit[] $produits
     */
    private function historiqueMouvements(ObjectManager $manager, Boutique $boutique, Employe $employe, array $produits): void
    {
        $aujourdHui = new \DateTimeImmutable('today');
        $joursEtVariations = [-12 => 8, -10 => -3, -8 => 5, -6 => -6, -4 => 4, -2 => -2];

        foreach ($produits as $produit) {
            foreach ($joursEtVariations as $decalageJours => $quantite) {
                $type = $quantite >= 0 ? TypeMouvement::RECEPTION : TypeMouvement::VENTE;
                $mouvement = new MouvementStock($type, $quantite, $produit, $boutique, $employe);
                $this->backdater($mouvement, 'dateMouvement', $aujourdHui->modify($decalageJours.' days'));
                $manager->persist($mouvement);
            }
        }

        $manager->flush();
    }

    private function commandeRecue(
        ObjectManager $manager,
        Boutique $boutique,
        Fournisseur $fournisseur,
        Employe $employe,
        Produit $produitA,
        Produit $produitB,
    ): Commande {
        $commande = new Commande($boutique, $fournisseur, $employe);
        $this->backdater($commande, 'dateCreation', (new \DateTimeImmutable('today'))->modify('-5 days'));

        $ligneA = new LigneCommande($commande, $produitA, 20, '0.95');
        $ligneA->setQuantiteRecue(20);
        $ligneB = new LigneCommande($commande, $produitB, 24, '3.80');
        $ligneB->setQuantiteRecue(24);
        $commande->ajouterLigne($ligneA);
        $commande->ajouterLigne($ligneB);
        $commande->setStatut(StatutCommande::RECUE);

        $manager->persist($commande);
        $manager->persist($ligneA);
        $manager->persist($ligneB);

        return $commande;
    }

    private function commandePartielle(
        ObjectManager $manager,
        Boutique $boutique,
        Fournisseur $fournisseur,
        Employe $employe,
        Produit $produitA,
        Produit $produitB,
    ): Commande {
        $commande = new Commande($boutique, $fournisseur, $employe);
        $this->backdater($commande, 'dateCreation', (new \DateTimeImmutable('today'))->modify('-2 days'));

        $ligneA = new LigneCommande($commande, $produitA, 20, '2.10');
        $ligneA->setQuantiteRecue(20);
        // Ligne B volontairement partielle — utiliser l'écran de réception
        // pour terminer la livraison (6 unités restantes) et vérifier le
        // passage automatique au statut RECUE.
        $ligneB = new LigneCommande($commande, $produitB, 12, '1.50');
        $ligneB->setQuantiteRecue(6);
        $commande->ajouterLigne($ligneA);
        $commande->ajouterLigne($ligneB);
        $commande->setStatut(StatutCommande::RECUE_PARTIELLE);

        $manager->persist($commande);
        $manager->persist($ligneA);
        $manager->persist($ligneB);

        return $commande;
    }

    private function commandeEnvoyee(
        ObjectManager $manager,
        Boutique $boutique,
        Fournisseur $fournisseur,
        Employe $employe,
        Produit $produitA,
        Produit $produitB,
    ): Commande {
        $commande = new Commande($boutique, $fournisseur, $employe);
        $this->backdater($commande, 'dateCreation', (new \DateTimeImmutable('today'))->modify('-1 days'));

        // Rien reçu — prête pour tester la réception complète depuis l'UI.
        $ligneA = new LigneCommande($commande, $produitA, 15, '1.40');
        $ligneB = new LigneCommande($commande, $produitB, 15, '0.95');
        $commande->ajouterLigne($ligneA);
        $commande->ajouterLigne($ligneB);
        $commande->setStatut(StatutCommande::ENVOYEE);

        $manager->persist($commande);
        $manager->persist($ligneA);
        $manager->persist($ligneB);

        return $commande;
    }

    private function backdater(object $entity, string $propriete, \DateTimeImmutable $date): void
    {
        $reflection = new \ReflectionProperty($entity, $propriete);
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $date);
    }
}
