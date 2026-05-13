<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Appointment;
use App\Enum\AppointmentMode;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Security\Voter\AppointmentVoter;
use App\Service\VideoRoomGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class VisioController extends AbstractController
{
    private const OPEN_BEFORE_MINUTES = 10;

    private const OPEN_AFTER_MINUTES = 30;

    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly VideoRoomGenerator $videoRooms,
    ) {}

    #[Route('/app/visio/{appointmentId}', name: 'app_visio', methods: ['GET'], requirements: ['appointmentId' => '\d+'])]
    public function room(int $appointmentId): Response
    {
        $appointment = $this->appointments->find($appointmentId);
        if (!$appointment instanceof Appointment) {
            throw new NotFoundHttpException('Rendez-vous introuvable.');
        }

        $this->denyAccessUnlessGranted(AppointmentVoter::VIEW, $appointment);

        if (AppointmentMode::VIDEO !== $appointment->getSlot()->getMode()) {
            return $this->render('visio/not_video.html.twig', ['appointment' => $appointment]);
        }

        if (AppointmentStatus::CONFIRMED !== $appointment->getStatus()) {
            return $this->render('visio/wrong_status.html.twig', ['appointment' => $appointment]);
        }

        $now = new \DateTimeImmutable();
        $openFrom = $appointment->getSlot()->getStartAt()->modify('-' . self::OPEN_BEFORE_MINUTES . ' minutes');
        $openUntil = $appointment->getSlot()->getEndAt()->modify('+' . self::OPEN_AFTER_MINUTES . ' minutes');

        if ($now < $openFrom || $now > $openUntil) {
            return $this->render('visio/out_of_window.html.twig', [
                'appointment' => $appointment,
                'openFrom' => $openFrom,
                'openUntil' => $openUntil,
                'now' => $now,
            ]);
        }

        return $this->render('visio/room.html.twig', [
            'appointment' => $appointment,
            'room' => $this->videoRooms->ensureFor($appointment),
        ]);
    }
}
