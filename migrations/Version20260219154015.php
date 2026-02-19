<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219154015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, fullname VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL, company VARCHAR(150) DEFAULT NULL, message LONGTEXT NOT NULL, create_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prosect (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(15) DEFAULT NULL, lastname VARCHAR(15) DEFAULT NULL, email VARCHAR(15) DEFAULT NULL, tel VARCHAR(15) DEFAULT NULL, datenaissane DATE DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE prosect');
    }
}
