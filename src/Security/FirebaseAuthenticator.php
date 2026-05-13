<?php

declare(strict_types=1);

namespace App\Security;

use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentifie les requêtes en lisant le cookie httpOnly 'fb_token'
 * et en vérifiant l'ID token via Firebase Admin SDK.
 *
 * Le cookie est posé par /auth/sync après login front Firebase JS.
 */
final class FirebaseAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const COOKIE_NAME = 'fb_token';

    public function __construct(
        private readonly FirebaseAuth $firebaseAuth,
        private readonly UrlGeneratorInterface $urls,
        private readonly LoggerInterface $logger,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->cookies->has(self::COOKIE_NAME);
    }

    public function authenticate(Request $request): Passport
    {
        $idToken = (string) $request->cookies->get(self::COOKIE_NAME, '');

        if ('' === $idToken) {
            throw new AuthenticationException('Cookie fb_token vide.');
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            $this->logger->warning('Firebase ID token rejeté', ['reason' => $e->getMessage()]);
            throw new AuthenticationException('Token Firebase invalide ou expiré.', previous: $e);
        }

        $uid = (string) $verified->claims()->get('sub');

        return new SelfValidatingPassport(new UserBadge($uid));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // Pas de redirection — on laisse la requête continuer vers son contrôleur.
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Cookie présent mais invalide → on l'efface et on renvoie sur login.
        $response = new RedirectResponse($this->urls->generate('app_login'));
        $response->headers->clearCookie(self::COOKIE_NAME, '/', null, true, true);
        return $response;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urls->generate('app_login'));
    }
}
