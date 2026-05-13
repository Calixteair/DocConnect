<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513215401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CASCADE delete sur appointments.patient_id pour permettre la suppression admin d\'un patient.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY `FK_6A41727A6B899279`');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A6B899279');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT `FK_6A41727A6B899279` FOREIGN KEY (patient_id) REFERENCES patients (id)');
    }
}
