<?php

declare(strict_types=1);

namespace App\Chat;

use App\Entity\ChatMessage;
use App\Entity\ChatSession;
use App\Entity\User;
use App\Enum\ChatRole;
use App\Repository\ChatMessageRepository;
use App\Repository\ChatSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Chaîne : intent dur → cache → LLM. Persiste la conversation en DB.
 *
 * En MVP on retourne la réponse complète d'un coup (pas de streaming token-par-token
 * côté client) — c'est suffisant pour un widget de chat et ça évite de gérer
 * un buffer SSE complexe.
 */
final class ChatOrchestrator
{
    private const CACHE_TTL_SECONDS = 3600;
    private const HISTORY_DEPTH = 6;

    public function __construct(
        private readonly IntentMatcher $intents,
        private readonly OpenRouterClient $llm,
        private readonly EntityManagerInterface $em,
        private readonly ChatSessionRepository $sessions,
        private readonly ChatMessageRepository $messages,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{reply: string, source: 'intent'|'cache'|'llm', sessionId: int}
     */
    public function handle(User $user, string $question): array
    {
        $question = trim($question);
        if ('' === $question) {
            throw new \InvalidArgumentException('Question vide.');
        }

        $session = $this->sessions->findActiveByUser($user) ?? new ChatSession($user);
        $isNewSession = null === $session->getId();
        $this->em->persist($session);
        if ($isNewSession) {
            // Flush dès maintenant pour obtenir un ID — ChatMessageRepository en a besoin
            // pour récupérer l'historique avant l'appel LLM.
            $this->em->flush();
        }

        $userMessage = new ChatMessage($session, ChatRole::USER, $question);
        $this->em->persist($userMessage);

        // 1) Intent matcher (réponses en dur).
        $intentReply = $this->intents->match($question);
        if (null !== $intentReply) {
            $this->logger->info('Chat intent hit', ['user_id' => $user->getId()]);
            return $this->persistReply($session, $intentReply, 'intent');
        }

        // 2) Cache — clé par question normalisée. Hit = pas d'appel LLM.
        $cacheKey = 'chat_' . hash('sha256', mb_strtolower($question));
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $reply = (string) $item->get();
            $this->logger->info('Chat cache hit', ['user_id' => $user->getId()]);
            return $this->persistReply($session, $reply, 'cache');
        }

        // 3) Appel LLM.
        try {
            $reply = $this->callLlm($session, $question);
        } catch (\Throwable $e) {
            $this->logger->error('LLM call failed', ['reason' => $e->getMessage()]);
            $fallback = "Je rencontre un souci pour vous répondre. Réessayez dans un instant, ou décrivez votre besoin différemment.";
            return $this->persistReply($session, $fallback, 'llm');
        }

        $item->set($reply);
        $item->expiresAfter(self::CACHE_TTL_SECONDS);
        $this->cache->save($item);

        return $this->persistReply($session, $reply, 'llm');
    }

    private function callLlm(ChatSession $session, string $question): string
    {
        $messages = [
            ['role' => 'system', 'content' => SystemPrompt::get()],
        ];

        // Historique court pour donner du contexte au LLM.
        $history = $this->messages->findBySession($session);
        $tail = array_slice($history, -self::HISTORY_DEPTH);
        foreach ($tail as $past) {
            $messages[] = [
                'role' => match ($past->getRole()) {
                    ChatRole::USER => 'user',
                    ChatRole::ASSISTANT => 'assistant',
                    ChatRole::SYSTEM => 'system',
                },
                'content' => $past->getContent(),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        $reply = $this->llm->complete($messages);
        $this->lastCallSource = 'llm';
        return $reply;
    }

    /**
     * @return array{reply: string, source: 'intent'|'cache'|'llm', sessionId: int}
     */
    private function persistReply(ChatSession $session, string $reply, string $source): array
    {
        $assistantMessage = new ChatMessage($session, ChatRole::ASSISTANT, $reply);
        $session->addMessage($assistantMessage);
        $this->em->persist($assistantMessage);
        $this->em->flush();

        return [
            'reply' => $reply,
            'source' => $source,
            'sessionId' => (int) $session->getId(),
        ];
    }

    /**
     * Mode streaming : yield des chunks au fur et à mesure.
     * Persiste user message au début, assistant message complet à la fin.
     *
     * @return \Generator<int, array{type: 'meta'|'chunk'|'done'|'error', data: array}>
     */
    public function handleStream(User $user, string $question): \Generator
    {
        $question = trim($question);
        if ('' === $question) {
            yield ['type' => 'error', 'data' => ['message' => 'Question vide.']];
            return;
        }

        $session = $this->sessions->findActiveByUser($user) ?? new ChatSession($user);
        $isNewSession = null === $session->getId();
        $this->em->persist($session);
        if ($isNewSession) {
            $this->em->flush();
        }

        $userMessage = new ChatMessage($session, ChatRole::USER, $question);
        $this->em->persist($userMessage);
        $this->em->flush();

        // 1) Intent matcher → réponse instantanée, "stream" en un seul chunk.
        $intentReply = $this->intents->match($question);
        if (null !== $intentReply) {
            yield ['type' => 'meta', 'data' => ['source' => 'intent', 'sessionId' => $session->getId()]];
            yield ['type' => 'chunk', 'data' => ['content' => $intentReply]];
            $this->persistReply($session, $intentReply, 'intent');
            yield ['type' => 'done', 'data' => []];
            return;
        }

        // 2) Cache.
        $cacheKey = 'chat_' . hash('sha256', mb_strtolower($question));
        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $reply = (string) $item->get();
            yield ['type' => 'meta', 'data' => ['source' => 'cache', 'sessionId' => $session->getId()]];
            yield ['type' => 'chunk', 'data' => ['content' => $reply]];
            $this->persistReply($session, $reply, 'cache');
            yield ['type' => 'done', 'data' => []];
            return;
        }

        // 3) LLM en streaming.
        yield ['type' => 'meta', 'data' => ['source' => 'llm', 'sessionId' => $session->getId()]];

        $messages = $this->buildMessagesForLlm($session, $question);
        $fullReply = '';

        try {
            foreach ($this->llm->streamComplete($messages) as $chunk) {
                $fullReply .= $chunk;
                yield ['type' => 'chunk', 'data' => ['content' => $chunk]];
            }
        } catch (\Throwable $e) {
            $this->logger->error('LLM streaming failed', ['reason' => $e->getMessage()]);
            yield ['type' => 'error', 'data' => ['message' => 'Le chatbot rencontre un souci.']];
            return;
        }

        if ('' === trim($fullReply)) {
            yield ['type' => 'error', 'data' => ['message' => 'Réponse vide du modèle.']];
            return;
        }

        $item->set($fullReply);
        $item->expiresAfter(self::CACHE_TTL_SECONDS);
        $this->cache->save($item);

        $this->persistReply($session, $fullReply, 'llm');
        yield ['type' => 'done', 'data' => []];
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function buildMessagesForLlm(ChatSession $session, string $question): array
    {
        $messages = [['role' => 'system', 'content' => SystemPrompt::get()]];

        $history = $this->messages->findBySession($session);
        $tail = array_slice($history, -self::HISTORY_DEPTH);
        foreach ($tail as $past) {
            // Exclut le message user qu'on vient de persister, déjà dans $question.
            if ($past->getContent() === $question && ChatRole::USER === $past->getRole()) {
                continue;
            }
            $messages[] = [
                'role' => match ($past->getRole()) {
                    ChatRole::USER => 'user',
                    ChatRole::ASSISTANT => 'assistant',
                    ChatRole::SYSTEM => 'system',
                },
                'content' => $past->getContent(),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];
        return $messages;
    }
}
