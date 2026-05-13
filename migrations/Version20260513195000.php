<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addresses (id INT AUTO_INCREMENT NOT NULL, street VARCHAR(200) NOT NULL, city VARCHAR(80) NOT NULL, postal_code VARCHAR(8) NOT NULL, latitude NUMERIC(9, 6) DEFAULT NULL, longitude NUMERIC(9, 6) DEFAULT NULL, doctor_id INT NOT NULL, INDEX IDX_6FCA751687F4FB17 (doctor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE appointments (id INT AUTO_INCREMENT NOT NULL, motif LONGTEXT DEFAULT NULL, status VARCHAR(12) DEFAULT \'PENDING\' NOT NULL, video_room VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, slot_id INT NOT NULL, patient_id INT NOT NULL, UNIQUE INDEX UNIQ_6A41727A59E5119C (slot_id), INDEX IDX_6A41727A6B899279 (patient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_messages (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(12) NOT NULL, content LONGTEXT NOT NULL, tokens INT DEFAULT NULL, created_at DATETIME NOT NULL, session_id INT NOT NULL, INDEX IDX_EF20C9A6613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_sessions (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, last_message_at DATETIME DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_AE424A23A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE doctors (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(120) NOT NULL, rpps VARCHAR(11) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, price INT NOT NULL, accept_visio TINYINT NOT NULL, languages JSON NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_B67687BEA76ED395 (user_id), UNIQUE INDEX uniq_doctors_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE doctor_specialty (doctor_id INT NOT NULL, specialty_id INT NOT NULL, INDEX IDX_2F74C70787F4FB17 (doctor_id), INDEX IDX_2F74C7079A353316 (specialty_id), PRIMARY KEY (doctor_id, specialty_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE patients (id INT AUTO_INCREMENT NOT NULL, birthdate DATETIME NOT NULL, gender VARCHAR(8) NOT NULL, allergies LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_2CCC2E2CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE slots (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(8) DEFAULT \'OPEN\' NOT NULL, mode VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, doctor_id INT NOT NULL, INDEX IDX_C87435D087F4FB17 (doctor_id), INDEX idx_slots_doctor_start (doctor_id, start_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE specialties (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, UNIQUE INDEX uniq_specialties_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA751687F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A59E5119C FOREIGN KEY (slot_id) REFERENCES slots (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_EF20C9A6613FECDF FOREIGN KEY (session_id) REFERENCES chat_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_sessions ADD CONSTRAINT FK_AE424A23A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE doctors ADD CONSTRAINT FK_B67687BEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_specialty ADD CONSTRAINT FK_2F74C70787F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_specialty ADD CONSTRAINT FK_2F74C7079A353316 FOREIGN KEY (specialty_id) REFERENCES specialties (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slots ADD CONSTRAINT FK_C87435D087F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA751687F4FB17');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A59E5119C');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A6B899279');
        $this->addSql('ALTER TABLE chat_messages DROP FOREIGN KEY FK_EF20C9A6613FECDF');
        $this->addSql('ALTER TABLE chat_sessions DROP FOREIGN KEY FK_AE424A23A76ED395');
        $this->addSql('ALTER TABLE doctors DROP FOREIGN KEY FK_B67687BEA76ED395');
        $this->addSql('ALTER TABLE doctor_specialty DROP FOREIGN KEY FK_2F74C70787F4FB17');
        $this->addSql('ALTER TABLE doctor_specialty DROP FOREIGN KEY FK_2F74C7079A353316');
        $this->addSql('ALTER TABLE patients DROP FOREIGN KEY FK_2CCC2E2CA76ED395');
        $this->addSql('ALTER TABLE slots DROP FOREIGN KEY FK_C87435D087F4FB17');
        $this->addSql('DROP TABLE addresses');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE chat_messages');
        $this->addSql('DROP TABLE chat_sessions');
        $this->addSql('DROP TABLE doctors');
        $this->addSql('DROP TABLE doctor_specialty');
        $this->addSql('DROP TABLE patients');
        $this->addSql('DROP TABLE slots');
        $this->addSql('DROP TABLE specialties');
    }
}
