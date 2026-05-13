<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;

final class VideoRoomGenerator
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function ensureFor(Appointment $appointment): string
    {
        $existing = $appointment->getVideoRoom();
        if (null !== $existing) {
            return $existing;
        }

        $name = sprintf('docconnect-%d-%s', (int) $appointment->getId(), bin2hex(random_bytes(8)));
        $appointment->setVideoRoom($name);
        $this->em->flush();

        return $name;
    }
}
