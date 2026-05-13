<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\DoctorFormData;
use App\Entity\Doctor;
use App\Repository\DoctorRepository;
use App\Repository\SpecialtyRepository;
use App\Service\DoctorAdminService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminDoctorController extends AbstractController
{
    public function __construct(
        private readonly DoctorRepository $doctors,
        private readonly SpecialtyRepository $specialties,
        private readonly DoctorAdminService $admin,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/admin/medecins', name: 'app_admin_doctors', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $specialtyId = (int) $request->query->get('specialty', '0');

        $qb = $this->doctors->createQueryBuilder('d')
            ->innerJoin('d.user', 'u')->addSelect('u')
            ->leftJoin('d.specialties', 's')->addSelect('s')
            ->leftJoin('d.addresses', 'a')->addSelect('a')
            ->orderBy('u.lastName', 'ASC');

        if ('' !== $q) {
            $qb->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q OR d.slug LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($specialtyId > 0) {
            $qb->andWhere(':sid MEMBER OF d.specialties')->setParameter('sid', $specialtyId);
        }

        return $this->render('admin/doctors_list.html.twig', [
            'doctors' => $qb->getQuery()->getResult(),
            'q' => $q,
            'specialtyFilter' => $specialtyId,
            'specialties' => $this->specialties->findBy([], ['label' => 'ASC']),
        ]);
    }

    #[Route('/admin/medecins/nouveau', name: 'app_admin_doctor_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_doctor_new', (string) $request->request->get('_token', ''))) {
                $this->addFlash('error', 'Session expirée, réessayez.');
                return $this->redirectToRoute('app_admin_doctor_new');
            }

            $password = trim((string) $request->request->get('password', ''));
            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit faire au moins 6 caractères.');
                return $this->renderForm($request, null);
            }

            try {
                $data = $this->bindFormData($request);
                $doctor = $this->admin->create($data, $password);
                $this->addFlash('success', sprintf('Médecin "%s" créé.', $doctor->getDisplayName()));
                return $this->redirectToRoute('app_admin_doctors');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->logger->error('Admin doctor create failed', ['reason' => $e->getMessage()]);
                $this->addFlash('error', 'Impossible de créer le médecin (email déjà pris ou données invalides).');
            }
        }

        return $this->renderForm($request, null);
    }

    #[Route('/admin/medecins/{id}/edit', name: 'app_admin_doctor_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $doctor = $this->doctors->find($id);
        if (!$doctor instanceof Doctor) {
            throw new NotFoundHttpException('Médecin introuvable.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_doctor_edit_' . $id, (string) $request->request->get('_token', ''))) {
                $this->addFlash('error', 'Session expirée, réessayez.');
                return $this->redirectToRoute('app_admin_doctor_edit', ['id' => $id]);
            }

            try {
                $data = $this->bindFormData($request);
                $this->admin->update($doctor, $data);
                $this->addFlash('success', 'Médecin mis à jour.');
                return $this->redirectToRoute('app_admin_doctors');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->logger->error('Admin doctor update failed', ['id' => $id, 'reason' => $e->getMessage()]);
                $this->addFlash('error', 'Impossible de mettre à jour ce médecin.');
            }
        }

        return $this->renderForm($request, $doctor);
    }

    #[Route('/admin/medecins/{id}/delete', name: 'app_admin_doctor_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): RedirectResponse
    {
        $doctor = $this->doctors->find($id);
        if (!$doctor instanceof Doctor) {
            throw new NotFoundHttpException('Médecin introuvable.');
        }

        if (!$this->isCsrfTokenValid('admin_doctor_delete_' . $id, (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Session expirée, réessayez.');
            return $this->redirectToRoute('app_admin_doctors');
        }

        $this->admin->delete($doctor);
        $this->addFlash('success', 'Médecin supprimé. Ses RDV ont été annulés en cascade.');
        return $this->redirectToRoute('app_admin_doctors');
    }

    private function renderForm(Request $request, ?Doctor $doctor): Response
    {
        return $this->render('admin/doctor_form.html.twig', [
            'doctor' => $doctor,
            'specialties' => $this->specialties->findBy([], ['label' => 'ASC']),
            'submitted' => $request->isMethod('POST') ? $request->request : null,
        ]);
    }

    private function bindFormData(Request $request): DoctorFormData
    {
        $r = $request->request;

        $languagesRaw = (string) $r->get('languages', '');
        $languages = array_values(array_filter(array_map('trim', explode(',', $languagesRaw))));

        $specialtyIds = array_map('intval', (array) $r->all('specialties'));

        return new DoctorFormData(
            firstName: trim((string) $r->get('firstName', '')),
            lastName: trim((string) $r->get('lastName', '')),
            email: trim((string) $r->get('email', '')),
            phone: trim((string) $r->get('phone', '')) ?: null,
            slug: trim((string) $r->get('slug', '')),
            rpps: trim((string) $r->get('rpps', '')) ?: null,
            bio: trim((string) $r->get('bio', '')) ?: null,
            price: (int) $r->get('price', '0'),
            acceptVisio: '1' === (string) $r->get('acceptVisio', '0'),
            languages: $languages,
            specialtyIds: $specialtyIds,
            street: trim((string) $r->get('street', '')),
            city: trim((string) $r->get('city', '')),
            postalCode: trim((string) $r->get('postalCode', '')),
        );
    }
}
