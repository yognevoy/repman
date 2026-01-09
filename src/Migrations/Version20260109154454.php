<?php

declare(strict_types=1);

namespace Buddy\Repman\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109154454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package ADD locked BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE organization_package ADD locked_version VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_package ADD locked_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization_package.locked_until IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization_package DROP locked');
        $this->addSql('ALTER TABLE organization_package DROP locked_version');
        $this->addSql('ALTER TABLE organization_package DROP locked_until');
    }
}
