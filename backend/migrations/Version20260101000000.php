<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hand-authored transcription of the Jalon 3 (Modélisation BDD) DDL, verbatim,
 * rather than an auto-diff — Doctrine's schema-diff tooling does not reliably
 * generate native PostgreSQL ENUM types. All 12 tables/enums are created here
 * even though only F1/F2 have working endpoints in this pass, since the full
 * schema was already finalized and 3NF-verified at Jalon 3.
 */
final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'StockPilot initial schema (Jalon 3 DDL): enums, 12 tables, indexes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TYPE role_employe AS ENUM ('GERANT','EMPLOYE')");
        $this->addSql("CREATE TYPE poste_employe AS ENUM ('RESPONSABLE','VENDEUR')");
        $this->addSql("CREATE TYPE statut_commande AS ENUM ('BROUILLON','ENVOYEE','RECUE_PARTIELLE','RECUE')");
        $this->addSql("CREATE TYPE type_mouvement AS ENUM ('RECEPTION','VENTE','AJUSTEMENT','TRANSFERT')");

        $this->addSql('
            CREATE TABLE employe (
                id_employe        SERIAL PRIMARY KEY,
                nom               VARCHAR(100) NOT NULL,
                prenom            VARCHAR(100) NOT NULL,
                email             VARCHAR(255) NOT NULL UNIQUE,
                mot_de_passe      VARCHAR(255) NOT NULL,
                role              role_employe NOT NULL,
                date_creation     TIMESTAMP NOT NULL DEFAULT now()
            )
        ');

        $this->addSql('
            CREATE TABLE boutique (
                id_boutique       SERIAL PRIMARY KEY,
                nom               VARCHAR(150) NOT NULL,
                adresse           VARCHAR(255) NOT NULL,
                ville             VARCHAR(100) NOT NULL
            )
        ');

        $this->addSql('
            CREATE TABLE affecter (
                id_employe        INT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE,
                id_boutique       INT NOT NULL REFERENCES boutique(id_boutique) ON DELETE CASCADE,
                poste_employe     poste_employe NOT NULL,
                PRIMARY KEY (id_employe, id_boutique)
            )
        ');

        $this->addSql('
            CREATE TABLE fournisseur (
                id_fournisseur    SERIAL PRIMARY KEY,
                nom               VARCHAR(150) NOT NULL,
                email_contact     VARCHAR(255),
                telephone         VARCHAR(30),
                delai_livraison_jours SMALLINT
            )
        ');

        $this->addSql('
            CREATE TABLE categorie (
                id_categorie      SERIAL PRIMARY KEY,
                nom               VARCHAR(100) NOT NULL UNIQUE
            )
        ');

        $this->addSql("
            CREATE TABLE produit (
                id_produit        SERIAL PRIMARY KEY,
                nom               VARCHAR(200) NOT NULL,
                code_barre        VARCHAR(30) UNIQUE,
                description       TEXT,
                prix_achat        NUMERIC(10,2) NOT NULL CHECK (prix_achat >= 0),
                unite             TEXT NOT NULL DEFAULT 'piece',
                quantite          INT NOT NULL DEFAULT 0,
                id_categorie      INT NOT NULL REFERENCES categorie(id_categorie) ON DELETE RESTRICT
            )
        ");
        $this->addSql('CREATE INDEX idx_produit_code_barre ON produit(code_barre)');

        $this->addSql('
            CREATE TABLE proposer (
                id_fournisseur    INT NOT NULL REFERENCES fournisseur(id_fournisseur) ON DELETE CASCADE,
                id_produit        INT NOT NULL REFERENCES produit(id_produit) ON DELETE CASCADE,
                reference_fournisseur VARCHAR(100),
                prix_fournisseur  NUMERIC(10,2) NOT NULL CHECK (prix_fournisseur >= 0),
                PRIMARY KEY (id_fournisseur, id_produit)
            )
        ');

        $this->addSql('
            CREATE TABLE stocker (
                id_produit            INT NOT NULL REFERENCES produit(id_produit) ON DELETE CASCADE,
                id_boutique           INT NOT NULL REFERENCES boutique(id_boutique) ON DELETE CASCADE,
                quantite_actuelle     INT NOT NULL DEFAULT 0 CHECK (quantite_actuelle >= 0),
                seuil_reappro         INT NOT NULL DEFAULT 0 CHECK (seuil_reappro >= 0),
                quantite_commande_reco INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id_produit, id_boutique)
            )
        ');

        $this->addSql("
            CREATE TABLE commande (
                id_commande       SERIAL PRIMARY KEY,
                statut            statut_commande NOT NULL DEFAULT 'BROUILLON',
                date_creation     TIMESTAMP NOT NULL DEFAULT now(),
                date_prevue       DATE,
                id_boutique       INT NOT NULL REFERENCES boutique(id_boutique) ON DELETE RESTRICT,
                id_fournisseur    INT NOT NULL REFERENCES fournisseur(id_fournisseur) ON DELETE RESTRICT,
                id_employe        INT NOT NULL REFERENCES employe(id_employe) ON DELETE RESTRICT
            )
        ");
        $this->addSql('CREATE INDEX idx_commande_statut ON commande(statut)');

        $this->addSql('
            CREATE TABLE ligne_commande (
                id_ligne_commande SERIAL PRIMARY KEY,
                id_commande       INT NOT NULL REFERENCES commande(id_commande) ON DELETE CASCADE,
                id_produit        INT NOT NULL REFERENCES produit(id_produit) ON DELETE RESTRICT,
                quantite_commandee INT NOT NULL CHECK (quantite_commandee > 0),
                quantite_recue    INT NOT NULL DEFAULT 0 CHECK (quantite_recue >= 0),
                prix_unitaire     NUMERIC(10,2) NOT NULL CHECK (prix_unitaire >= 0)
            )
        ');

        $this->addSql('
            CREATE TABLE mouvement_stock (
                id_mouvement      SERIAL PRIMARY KEY,
                type              type_mouvement NOT NULL,
                quantite          INT NOT NULL,
                date_mouvement    TIMESTAMP NOT NULL DEFAULT now(),
                id_produit        INT NOT NULL REFERENCES produit(id_produit) ON DELETE RESTRICT,
                id_boutique       INT NOT NULL REFERENCES boutique(id_boutique) ON DELETE RESTRICT,
                id_employe        INT NOT NULL REFERENCES employe(id_employe) ON DELETE RESTRICT,
                id_commande       INT REFERENCES commande(id_commande) ON DELETE SET NULL
            )
        ');
        $this->addSql('CREATE INDEX idx_mvt_produit_boutique ON mouvement_stock(id_produit, id_boutique)');
        $this->addSql('CREATE INDEX idx_mvt_date ON mouvement_stock(date_mouvement)');

        $this->addSql('
            CREATE TABLE notification (
                id_notification   SERIAL PRIMARY KEY,
                type              VARCHAR(50) NOT NULL,
                message           TEXT NOT NULL,
                lu                BOOLEAN NOT NULL DEFAULT FALSE,
                date_creation     TIMESTAMP NOT NULL DEFAULT now(),
                id_employe        INT NOT NULL REFERENCES employe(id_employe) ON DELETE CASCADE
            )
        ');
        $this->addSql('CREATE INDEX idx_notification_employe_lu ON notification(id_employe, lu)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE stocker');
        $this->addSql('DROP TABLE proposer');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('DROP TABLE affecter');
        $this->addSql('DROP TABLE boutique');
        $this->addSql('DROP TABLE employe');

        $this->addSql('DROP TYPE type_mouvement');
        $this->addSql('DROP TYPE statut_commande');
        $this->addSql('DROP TYPE poste_employe');
        $this->addSql('DROP TYPE role_employe');
    }
}
