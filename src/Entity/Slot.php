<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AppointmentMode;
use App\Enum\SlotStatus;
use App\Repository\SlotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SlotRepository::class)]
#[ORM\Table(name: 'slots')]
#[ORM\Index(name: 'idx_slots_doctor_start', columns: ['doctor_id', 'start_at'])]
class Slot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Doctor::class)]
    #[ORM\JoinColumn(name: 'doctor_id', nullable: false, onDelete: 'CASCADE')]
    private Doctor $doctor;

    #[ORM\Column(name: 'start_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(name: 'end_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThan(propertyPath: 'startAt')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(enumType: SlotStatus::class, length: 8, options: ['default' => 'OPEN'])]
    private SlotStatus $status = SlotStatus::OPEN;

    #[ORM\Column(enumType: AppointmentMode::class, length: 8)]
    #[Assert\NotNull]
    private AppointmentMode $mode;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Doctor $doctor, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, AppointmentMode $mode)
    {
        $this->doctor = $doctor;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->mode = $mode;
        $this->status = SlotStatus::OPEN;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getStatus(): SlotStatus
    {
        return $this->status;
    }

    public function setStatus(SlotStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMode(): AppointmentMode
    {
        return $this->mode;
    }

    public function setMode(AppointmentMode $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDurationMinutes(): int
    {
        return (int) (($this->endAt->getTimestamp() - $this->startAt->getTimestamp()) / 60);
    }

    public function isOpen(): bool
    {
        return $this->status === SlotStatus::OPEN;
    }

    public function isBooked(): bool
    {
        return $this->status === SlotStatus::BOOKED;
    }

    public function markBooked(): void
    {
        $this->status = SlotStatus::BOOKED;
    }

    public function markOpen(): void
    {
        $this->status = SlotStatus::OPEN;
    }
}
