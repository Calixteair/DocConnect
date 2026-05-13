<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\PatientRepository;
use App\Repository\SlotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AppController extends AbstractController
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly DoctorRepository $doctors,
        private readonly AppointmentRepository $appointments,
        private readonly SlotRepository $slots,
    ) {}

    #[Route('/app/mes-rdv', name: 'app_my_appointments', methods: ['GET'])]
    public function myAppointments(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (UserRole::PATIENT !== $user->getRole()) {
            // Un médecin connecté qui clique sur "Mes RDV" est redirigé vers son planning.
            return $this->redirectToRoute('app_doctor_planning');
        }

        $patient = $this->patients->findByUserId((int) $user->getId());
        if (null === $patient) {
            return $this->render('app/no_profile.html.twig');
        }

        return $this->render('app/my_appointments.html.twig', [
            'patient' => $patient,
            'upcoming' => $this->appointments->findUpcomingForPatient($patient),
            'past' => $this->appointments->findPastForPatient($patient),
            'now' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/app/planning', name: 'app_doctor_planning', methods: ['GET'])]
    public function doctorPlanning(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (UserRole::DOCTOR !== $user->getRole()) {
            // Un patient connecté est redirigé vers sa liste de RDV.
            return $this->redirectToRoute('app_my_appointments');
        }

        $doctor = $this->doctors->findOneBy(['user' => $user]);
        if (null === $doctor) {
            return $this->render('app/no_profile.html.twig');
        }

        $weekOffset = max(0, (int) $request->query->get('week', '0'));
        $weekStart = (new \DateTimeImmutable('today'))->modify('monday this week')
            ->modify(sprintf('+%d weeks', $weekOffset));
        $weekEnd = $weekStart->modify('+7 days');

        $slots = $this->slots->findForDoctorBetween($doctor, $weekStart, $weekEnd);

        // Bucket par jour pour affichage agenda.
        $bucket = [];
        foreach ($slots as $slot) {
            $dayKey = $slot->getStartAt()->format('Y-m-d');
            $bucket[$dayKey][] = $slot;
        }

        // Indexe les Appointments par slot_id pour éviter une requête par cellule en Twig.
        $appointmentsBySlot = [];
        foreach ($this->appointments->findForDoctorBetween($doctor, $weekStart, $weekEnd) as $appt) {
            $appointmentsBySlot[$appt->getSlot()->getId()] = $appt;
        }

        $days = [];
        for ($i = 0; $i < 6; $i++) {
            $days[] = $weekStart->modify(sprintf('+%d days', $i));
        }

        return $this->render('app/doctor_planning.html.twig', [
            'doctor' => $doctor,
            'days' => $days,
            'slotsByDay' => $bucket,
            'appointmentsBySlot' => $appointmentsBySlot,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd->modify('-1 day'),
            'weekOffset' => $weekOffset,
        ]);
    }
}
