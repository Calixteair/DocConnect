<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\UserAdminService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserAdminService $admin,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/admin', name: 'app_admin_home', methods: ['GET'])]
    public function home(): Response
    {
        $all = $this->users->findBy([], ['createdAt' => 'DESC']);

        $counts = [
            'total' => count($all),
            'patients' => 0,
            'doctors' => 0,
            'admins' => 0,
        ];
        foreach ($all as $u) {
            $counts[match ($u->getRole()) {
                UserRole::PATIENT => 'patients',
                UserRole::DOCTOR => 'doctors',
                UserRole::ADMIN => 'admins',
            }]++;
        }

        return $this->render('admin/home.html.twig', [
            'counts' => $counts,
            'recent' => array_slice($all, 0, 5),
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users', methods: ['GET'])]
    public function usersList(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $roleFilter = (string) $request->query->get('role', '');

        $qb = $this->users->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');
        if ('' !== $q) {
            $qb->andWhere('u.email LIKE :q OR u.firstName LIKE :q OR u.lastName LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        if ('' !== $roleFilter && null !== UserRole::tryFrom($roleFilter)) {
            $qb->andWhere('u.role = :role')->setParameter('role', UserRole::from($roleFilter));
        }

        return $this->render('admin/users_list.html.twig', [
            'users' => $qb->getQuery()->getResult(),
            'q' => $q,
            'roleFilter' => $roleFilter,
            'roles' => UserRole::cases(),
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function userEdit(int $id, Request $request): Response
    {
        $user = $this->users->find($id);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_user_edit_' . $id, (string) $request->request->get('_token', ''))) {
                $this->addFlash('error', 'Session expirée, réessayez.');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $id]);
            }

            try {
                $this->admin->updateProfile(
                    $user,
                    trim((string) $request->request->get('firstName', '')),
                    trim((string) $request->request->get('lastName', '')),
                    trim((string) $request->request->get('phone', '')) ?: null,
                    trim((string) $request->request->get('email', '')),
                );

                $newRoleRaw = (string) $request->request->get('role', '');
                $newRole = UserRole::tryFrom($newRoleRaw);
                if (null !== $newRole && $newRole !== $user->getRole()) {
                    $this->admin->changeRole($user, $newRole);
                }

                $this->addFlash('success', 'Utilisateur mis à jour.');
                return $this->redirectToRoute('app_admin_users');
            } catch (\Throwable $e) {
                $this->logger->error('Admin user edit failed', ['user_id' => $id, 'reason' => $e->getMessage()]);
                $this->addFlash('error', 'Impossible de mettre à jour cet utilisateur (email déjà pris ou erreur Firebase).');
            }
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function userDelete(int $id, Request $request): RedirectResponse
    {
        $user = $this->users->find($id);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        if (!$this->isCsrfTokenValid('admin_user_delete_' . $id, (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Session expirée, réessayez.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Garde-fou : un admin ne peut pas se suicider depuis l'UI.
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        try {
            $this->admin->delete($user);
            $this->addFlash('success', 'Utilisateur supprimé.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
