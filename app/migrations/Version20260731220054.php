<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731220054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adaugă configurația unică de branding, fără conținut binar.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE configuratie_branding (id INT NOT NULL, nume_aplicatie VARCHAR(100) NOT NULL, culoare_principala VARCHAR(7) NOT NULL, culoare_secundara VARCHAR(7) NOT NULL, logo_principal VARCHAR(255) DEFAULT NULL, logo_compact VARCHAR(255) DEFAULT NULL, favicon VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE configuratie_branding');
    }
}
