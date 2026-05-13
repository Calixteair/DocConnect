<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\User;
use App\Enum\Gender;
use App\Enum\UserRole;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use App\Security\FirebaseAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    private const COOKIE_TTL_SECONDS = 3600; // Firebase ID token expire en 1h.

    public function __construct(
        private readonly FirebaseAuth $firebaseAuth,
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function loginPage(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET'])]
    public function signupPage(): Response
    {
        return $this->render('auth/signup.html.twig');
    }

    /**
     * Reçoit un ID token Firebase frais (côté front après auth réussie),
     * vérifie le token, crée ou met à jour le User MariaDB, pose le cookie httpOnly.
     */
    #[Route('/auth/sync', name: 'app_auth_sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $idToken = is_array($payload) && isset($payload['idToken']) ? (string) $payload['idToken'] : '';
        $firstName = is_array($payload) ? trim((string) ($payload['firstName'] ?? '')) : '';
        $lastName = is_array($payload) ? trim((string) ($payload['lastName'] ?? '')) : '';

        if ('' === $idToken) {
            return new JsonResponse(['error' => 'Indiquez un idToken Firebase.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            $this->logger->warning('Firebase token rejeté à /auth/sync', ['reason' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Token Firebase invalide.'], Response::HTTP_UNAUTHORIZED);
        }

        $uid = (string) $verified->claims()->get('sub');
        $email = (string) $verified->claims()->get('email');

        if ('' === $email) {
            // L'idToken ne porte pas toujours l'email si non vérifié — on le récupère via Admin SDK.
            try {
                $email = $this->firebaseAuth->getUser($uid)->email ?? '';
            } catch (\Throwable $e) {
                $this->logger->error('Impossible de récupérer l\'email Firebase', ['uid' => $uid, 'reason' => $e->getMessage()]);
                return new JsonResponse(['error' => 'Email Firebase introuvable.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $user = $this->users->findByFirebaseUid($uid);

        if (null === $user) {
            $user = new User(
                firebaseUid: $uid,
                email: $email,
                firstName: '' !== $firstName ? $firstName : 'Patient',
                lastName: '' !== $lastName ? $lastName : 'DocConnect',
            );
            $user->setRole(UserRole::PATIENT);
            $this->em->persist($user);

            // Auto-création du Patient lié — sans ça l'utilisateur ne pourrait pas
            // réserver de RDV. Date de naissance / genre placeholders ; on les
            // affinera plus tard (page de profil hors MVP).
            $patient = new Patient(
                user: $user,
                birthdate: new \DateTimeImmutable('1990-01-01'),
                gender: Gender::OTHER,
            );
            $this->em->persist($patient);
        } else {
            // Maintien sync : si l'email Firebase a changé, on met à jour.
            if ($user->getEmail() !== $email) {
                $user->setEmail($email);
            }
            // Filet de sécurité : si un User existe sans Patient (cas legacy ou
            // role PATIENT promu depuis ADMIN/DOCTOR), on en crée un.
            if (UserRole::PATIENT === $user->getRole()
                && null === $this->patients->findByUserId((int) $user->getId())) {
                $patient = new Patient(
                    user: $user,
                    birthdate: new \DateTimeImmutable('1990-01-01'),
                    gender: Gender::OTHER,
                );
                $this->em->persist($patient);
            }
        }

        $this->em->flush();

        $response = new JsonResponse([
            'ok' => true,
            'user' => [
                'uid' => $user->getFirebaseUid(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'role' => $user->getRole()->value,
            ],
        ]);

        $response->headers->setCookie($this->buildCookie($idToken));
        return $response;
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(Request $request, Security $security): RedirectResponse
    {
        // 1) Invalide le token + la session Symfony (sinon il re-authentifie
        //    depuis _security_main au prochain hit, même sans le cookie Firebase).
        $security->logout(validateCsrfToken: false);
        $request->getSession()->invalidate();

        // 2) Efface le cookie httpOnly Firebase ID token.
        $response = new RedirectResponse($this->generateUrl('app_home'));
        $secure = $this->getParameter('kernel.environment') !== 'dev';
        $response->headers->clearCookie(FirebaseAuthenticator::COOKIE_NAME, '/', null, $secure, true, 'lax');

        return $response;
    }

    private function buildCookie(string $idToken): Cookie
    {
        return Cookie::create(FirebaseAuthenticator::COOKIE_NAME)
            ->withValue($idToken)
            ->withPath('/')
            ->withSecure($this->getParameter('kernel.environment') !== 'dev')
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withExpires(time() + self::COOKIE_TTL_SECONDS);
    }
}
