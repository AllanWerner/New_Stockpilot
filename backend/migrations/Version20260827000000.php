<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F1 "Gérer l'organisation": adds an actif flag to employe/boutique so a
 * gérant can deactivate an account or a boutique without deleting history —
 * commande/mouvement_stock reference both tables with ON DELETE RESTRICT
 * (see Version20260101000000), so a hard delete only ever works for records
 * with no activity yet; deactivation is the everyday lever.
 */
final class Version20260827000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add employe.actif and boutique.actif for F1 activation/deactivation.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employe ADD COLUMN actif BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE boutique ADD COLUMN actif BOOLEAN NOT NULL DEFAULT TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE boutique DROP COLUMN actif');
        $this->addSql('ALTER TABLE employe DROP COLUMN actif');
    }
}
