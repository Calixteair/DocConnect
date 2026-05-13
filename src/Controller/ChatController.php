<?php

declare(strict_types=1);

namespace App\Controller;

use App\Chat\ChatOrchestrator;
use App\Entity\User;
use App\Enum\ChatRole;
use App\Repository\ChatMessageRepository;
use App\Repository\ChatSessionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatOrchestrator $orchestrator,
        private readonly ChatSessionRepository $sessions,
        private readonly ChatMessageRepository $messages,
        private readonly RateLimiterFactory $chatMessageLimiter,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Envoie un message au chatbot et reçoit la réponse complète.
     * En cache hit ou intent match, c'est instantané. En LLM, ~1-3 s.
     */
    #[Route('/api/chat/message', name: 'app_chat_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Rate limit : 10 messages / minute / user — protège la facture OpenRouter.
        $limiter = $this->chatMessageLimiter->create((string) $user->getId());
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return new JsonResponse([
                'error' => 'Trop de messages. Réessayez dans une minute.',
                'retryAfter' => $limit->getRetryAfter()->getTimestamp() - time(),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $payload = json_decode($request->getContent(), true);
        $question = is_array($payload) ? trim((string) ($payload['message'] ?? '')) : '';

        if ('' === $question) {
            return new JsonResponse(['error' => 'Message vide.'], Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($question) > 1000) {
            return new JsonResponse(['error' => 'Message trop long (1000 caractères max).'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->orchestrator->handle($user, $question);
        } catch (\Throwable $e) {
            $this->logger->error('Chat orchestrator threw', ['reason' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Le chatbot est momentanément indisponible.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse([
            'reply' => $result['reply'],
            'source' => $result['source'],
            'sessionId' => $result['sessionId'],
        ]);
    }

    /**
     * Streaming endpoint : POST + body JSON {message}, retourne SSE.
     * Le client consomme via fetch + ReadableStream (EventSource ne supporte pas POST).
     */
    #[Route('/api/chat/stream', name: 'app_chat_stream', methods: ['POST'])]
    public function streamMessage(Request $request, ?Profiler $profiler = null): StreamedResponse
    {
        // Désactive le profiler pour cette requête — sinon il bufferise toute la
        // réponse en mémoire, ruinant le streaming SSE.
        $profiler?->disable();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $limit = $this->chatMessageLimiter->create((string) $user->getId())->consume(1);
        if (!$limit->isAccepted()) {
            return new StreamedResponse(function () {
                echo "event: error\ndata: " . json_encode(['message' => 'Trop de messages. Réessayez dans une minute.']) . "\n\n";
                flush();
            }, Response::HTTP_TOO_MANY_REQUESTS, $this->sseHeaders());
        }

        $payload = json_decode($request->getContent(), true);
        $question = is_array($payload) ? trim((string) ($payload['message'] ?? '')) : '';

        if ('' === $question || mb_strlen($question) > 1000) {
            return new StreamedResponse(function () {
                echo "event: error\ndata: " . json_encode(['message' => 'Message invalide.']) . "\n\n";
                flush();
            }, Response::HTTP_BAD_REQUEST, $this->sseHeaders());
        }

        $response = new StreamedResponse(function () use ($user, $question) {
            // Force le flush immédiat des buffers PHP.
            @ini_set('output_buffering', '0');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            ignore_user_abort(false);
            while (ob_get_level() > 0) { @ob_end_flush(); }
            ob_implicit_flush(true);

            // Padding initial 2 ko pour forcer nginx/proxy à flusher le head.
            echo ': ' . str_repeat(' ', 2048) . "\n\n";
            @flush();

            try {
                foreach ($this->orchestrator->handleStream($user, $question) as $event) {
                    echo "event: " . $event['type'] . "\n";
                    echo 'data: ' . json_encode($event['data'], JSON_UNESCAPED_UNICODE) . "\n\n";
                    @flush();
                }
            } catch (\Throwable $e) {
                $this->logger->error('Chat stream crashed', ['reason' => $e->getMessage()]);
                echo "event: error\ndata: " . json_encode(['message' => 'Erreur interne.']) . "\n\n";
                @flush();
            }
        });

        foreach ($this->sseHeaders() as $name => $value) {
            $response->headers->set($name, $value);
        }
        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function sseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no', // désactive le buffering nginx
            'Connection' => 'keep-alive',
        ];
    }

    /**
     * Historique des messages de la session active de l'utilisateur.
     * Utile au load initial du widget pour reprendre une conversation.
     */
    #[Route('/api/chat/history', name: 'app_chat_history', methods: ['GET'])]
    public function history(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $session = $this->sessions->findActiveByUser($user);

        if (null === $session) {
            return new JsonResponse(['messages' => []]);
        }

        $messages = array_map(static fn($m) => [
            'role' => match ($m->getRole()) {
                ChatRole::USER => 'user',
                ChatRole::ASSISTANT => 'assistant',
                ChatRole::SYSTEM => 'system',
            },
            'content' => $m->getContent(),
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $this->messages->findBySession($session));

        return new JsonResponse(['messages' => $messages, 'sessionId' => $session->getId()]);
    }
}
