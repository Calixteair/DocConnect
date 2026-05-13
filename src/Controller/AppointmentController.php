<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\User;
use App\Enum\AppointmentMode;
use App\Exception\SlotAlreadyBookedException;
use App\Repository\AppointmentRepository;
use App\Repository\PatientRepository;
use App\Security\Voter\AppointmentVoter;
use App\Service\AppointmentBookingService;
use App\Service\AppointmentMailer;
use App\Service\VideoRoomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AppointmentRepository $appointments,
        private readonly PatientRepository $patients,
        private readonly EntityManagerInterface $em,
        private readonly AppointmentMailer $mailer,
        private readonly VideoRoomGenerator $videoRooms,
    ) {}

    #[Route('/app/appointments', name: 'app_appointment_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('book_appointment', $token)) {
            $this->addFlash('error', 'Session expirée, merci de réessayer.');
            return $this->redirectToRoute('app_home');
        }

        /** @var User $user */
        $user = $this->getUser();
        $patient = $this->patients->findByUserId((int) $user->getId());
        if (null === $patient) {
            $this->addFlash('error', 'Profil patient introuvable.');
            return $this->redirectToRoute('app_home');
        }

        $slotId = (int) $request->request->get('slot_id', '0');
        $motif = (string) $request->request->get('motif', '');
        $doctorSlug = (string) $request->request->get('doctor_slug', '');

        try {
            $appointment = $this->booking->book($slotId, $patient, $motif);
        } catch (SlotAlreadyBookedException) {
            $this->addFlash('error', 'Ce créneau vient d\'être réservé. Choisissez-en un autre.');
            return $doctorSlug !== ''
                ? $this->redirectToRoute('app_doctor_show', ['slug' => $doctorSlug])
                : $this->redirectToRoute('app_doctor_search');
        }

        $this->addFlash('success', sprintf(
            'Rendez-vous confirmé pour le %s.',
            $appointment->getSlot()->getStartAt()->format('d/m/Y à H:i'),
        ));

        return $this->redirectToRoute('app_my_appointments');
    }

    #[Route('/app/appointments/{id}/cancel', name: 'app_appointment_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(int $id, Request $request): RedirectResponse
    {
        $appointment = $this->appointments->find($id);
        if (!$appointment instanceof Appointment) {
            throw new NotFoundHttpException('Rendez-vous introuvable.');
        }

        $this->denyAccessUnlessGranted(AppointmentVoter::CANCEL, $appointment);

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('cancel_appointment_' . $id, $token)) {
            $this->addFlash('error', 'Session expirée, merci de réessayer.');
            return $this->redirectToRoute('app_my_appointments');
        }

        /** @var User $user */
        $user = $this->getUser();
        $isDoctor = $appointment->getSlot()->getDoctor()->getUser()->getId() === $user->getId();

        // Règle métier patient : pas d'annulation dans les 2h avant le RDV.
        // Le médecin peut annuler à tout moment (urgence, indisponibilité).
        if (!$isDoctor) {
            $now = new \DateTimeImmutable();
            $twoHoursBefore = $appointment->getSlot()->getStartAt()->modify('-2 hours');
            if ($now > $twoHoursBefore) {
                $this->addFlash('error', 'Trop tard pour annuler ce rendez-vous (moins de 2 h avant).');
                return $this->redirectToRoute('app_my_appointments');
            }
        }

        $appointment->cancel();
        $appointment->getSlot()->markOpen();
        $this->em->flush();

        $this->mailer->sendCancellation($appointment);

        $this->addFlash('success', 'Rendez-vous annulé. Le créneau est de nouveau disponible.');

        return $this->redirectToRoute($isDoctor ? 'app_doctor_planning' : 'app_my_appointments');
    }

    #[Route('/app/appointments/{id}/confirm', name: 'app_appointment_confirm', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function confirm(int $id, Request $request): RedirectResponse
    {
        $appointment = $this->appointments->find($id);
        if (!$appointment instanceof Appointment) {
            throw new NotFoundHttpException('Rendez-vous introuvable.');
        }

        $this->denyAccessUnlessGranted(AppointmentVoter::CONFIRM, $appointment);

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('confirm_appointment_' . $id, $token)) {
            $this->addFlash('error', 'Session expirée, merci de réessayer.');
            return $this->redirectToRoute('app_doctor_planning');
        }

        $appointment->confirm();
        $this->em->flush();

        // Salle générée à la confirmation pour figurer dans le mail au patient.
        if (AppointmentMode::VIDEO === $appointment->getSlot()->getMode()) {
            $this->videoRooms->ensureFor($appointment);
        }

        $this->mailer->sendConfirmation($appointment);

        $this->addFlash('success', 'Rendez-vous confirmé. Patient notifié par mail.');
        return $this->redirectToRoute('app_doctor_planning');
    }

    #[Route('/app/appointments/{id}/mark-done', name: 'app_appointment_mark_done', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markDone(int $id, Request $request): RedirectResponse
    {
        $appointment = $this->appointments->find($id);
        if (!$appointment instanceof Appointment) {
            throw new NotFoundHttpException('Rendez-vous introuvable.');
        }

        $this->denyAccessUnlessGranted(AppointmentVoter::MARK_DONE, $appointment);

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('mark_done_appointment_' . $id, $token)) {
            $this->addFlash('error', 'Session expirée, merci de réessayer.');
            return $this->redirectToRoute('app_doctor_planning');
        }

        $appointment->markDone();
        $this->em->flush();

        $this->addFlash('success', 'Rendez-vous marqué comme terminé.');
        return $this->redirectToRoute('app_doctor_planning');
    }
}
