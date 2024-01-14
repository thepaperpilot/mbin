<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240114182048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site ADD forgejo TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD matrix TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD pages TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE site DROP forgejo');
        $this->addSql('ALTER TABLE site DROP matrix');
        $this->addSql('ALTER TABLE site DROP pages');
    }
}
