<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Doctor;
use App\Enum\AppointmentMode;
use App\Enum\SlotStatus;
use App\Repository\DoctorRepository;
use App\Repository\SlotRepository;
use App\Repository\SpecialtyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DoctorController extends AbstractController
{
    private const SEARCH_PAGE_SIZE = 12;
    private const CALENDAR_DAYS = 7;

    public function __construct(
        private readonly DoctorRepository $doctors,
        private readonly SpecialtyRepository $specialties,
        private readonly SlotRepository $slots,
    ) {}

    #[Route('/medecins', name: 'app_doctor_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $specialtySlug = trim((string) $request->query->get('specialty', ''));
        $city = trim((string) $request->query->get('city', ''));
        $page = max(1, (int) $request->query->get('page', '1'));

        $offset = ($page - 1) * self::SEARCH_PAGE_SIZE;
        $total = $this->doctors->countForSearch($city, $specialtySlug ?: null);
        $results = $this->doctors->findForSearch($city, $specialtySlug ?: null, self::SEARCH_PAGE_SIZE, $offset);

        $totalPages = (int) ceil($total / self::SEARCH_PAGE_SIZE);

        return $this->render('doctor/search.html.twig', [
            'doctors' => $results,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, $totalPages),
            'specialties' => $this->specialties->findBy([], ['label' => 'ASC']),
            'specialty' => $specialtySlug,
            'city' => $city,
        ]);
    }

    #[Route('/medecin/{slug}', name: 'app_doctor_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, Request $request): Response
    {
        $doctor = $this->doctors->findBySlug($slug);
        if (!$doctor instanceof Doctor) {
            throw new NotFoundHttpException(sprintf('Médecin inconnu : %s.', $slug));
        }

        $weekOffset = max(0, (int) $request->query->get('week', '0'));
        $modeParam = strtolower((string) $request->query->get('mode', 'physical'));
        $mode = 'visio' === $modeParam ? AppointmentMode::VIDEO : AppointmentMode::PHYSICAL;

        $start = (new \DateTimeImmutable('today'))->modify(sprintf('+%d days', $weekOffset * self::CALENDAR_DAYS));
        $end = $start->modify(sprintf('+%d days', self::CALENDAR_DAYS));

        $slots = $this->slots->findOpenForDoctorWindow($doctor, $start, $end, $mode);

        // Bucket par YYYY-MM-DD pour faciliter l'affichage en colonnes.
        $slotsByDay = [];
        foreach ($slots as $slot) {
            $key = $slot->getStartAt()->format('Y-m-d');
            $slotsByDay[$key][] = $slot;
        }

        return $this->render('doctor/show.html.twig', [
            'doctor' => $doctor,
            'slotsByDay' => $slotsByDay,
            'mode' => $mode->value,
            'weekOffset' => $weekOffset,
            'windowStart' => $start,
            'windowEnd' => $end->modify('-1 day'),
            'days' => $this->buildDays($start),
        ]);
    }

    /**
     * @return list<\DateTimeImmutable>
     */
    private function buildDays(\DateTimeImmutable $start): array
    {
        $days = [];
        for ($i = 0; $i < self::CALENDAR_DAYS; $i++) {
            $days[] = $start->modify(sprintf('+%d days', $i));
        }
        return $days;
    }
}
