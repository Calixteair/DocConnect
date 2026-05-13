<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointments')]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Slot::class)]
    #[ORM\JoinColumn(name: 'slot_id', unique: true, nullable: false, onDelete: 'CASCADE')]
    private Slot $slot;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', nullable: false, onDelete: 'RESTRICT')]
    private Patient $patient;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $motif = null;

    #[ORM\Column(enumType: AppointmentStatus::class, length: 12, options: ['default' => 'PENDING'])]
    private AppointmentStatus $status = AppointmentStatus::PENDING;

    #[ORM\Column(name: 'video_room', length: 120, nullable: true)]
    private ?string $videoRoom = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'confirmed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(name: 'cancelled_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct(Slot $slot, Patient $patient)
    {
        $this->slot = $slot;
        $this->patient = $patient;
        $this->status = AppointmentStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlot(): Slot
    {
        return $this->slot;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;
        return $this;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function getVideoRoom(): ?string
    {
        return $this->videoRoom;
    }

    public function setVideoRoom(?string $videoRoom): self
    {
        $this->videoRoom = $videoRoom;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function confirm(): void
    {
        $this->status = AppointmentStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        $this->status = AppointmentStatus::CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
    }

    public function markDone(): void
    {
        $this->status = AppointmentStatus::DONE;
    }

    public function isUpcoming(): bool
    {
        return $this->status !== AppointmentStatus::CANCELLED
            && $this->status !== AppointmentStatus::DONE;
    }
}
