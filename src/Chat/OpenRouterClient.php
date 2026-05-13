<?php

declare(strict_types=1);

namespace App\Chat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Client OpenRouter — appels chat completions, non streaming pour MVP
 * (le streaming SSE est géré au niveau ChatController qui re-stream
 * depuis ce client en mode chunked).
 */
final class OpenRouterClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly string $appUrl,
        private readonly string $appName,
    ) {}

    /**
     * @param list<array{role: string, content: string}> $messages
     */
    public function complete(array $messages): string
    {
        if ('' === $this->apiKey) {
            throw new \RuntimeException('Clé OpenRouter manquante. Renseignez OPENROUTER_API_KEY dans .env.local.');
        }

        $response = $this->httpClient->request('POST', $this->baseUrl . '/chat/completions', [
            'headers' => $this->headers(),
            'json' => [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 600,
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray(throw: false);

        if ($response->getStatusCode() >= 400) {
            $this->logger->error('OpenRouter HTTP error', [
                'status' => $response->getStatusCode(),
                'body' => $data,
            ]);
            throw new \RuntimeException(sprintf(
                'OpenRouter a répondu %d (%s)',
                $response->getStatusCode(),
                $data['error']['message'] ?? 'sans détail',
            ));
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        if ('' === trim($content)) {
            throw new \RuntimeException('Réponse OpenRouter vide.');
        }

        return $content;
    }

    /**
     * Itère les chunks de contenu de la réponse SSE OpenRouter.
     * Yield chaque token / fragment au fur et à mesure qu'il arrive.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return \Generator<int, string>
     */
    public function streamComplete(array $messages): \Generator
    {
        if ('' === $this->apiKey) {
            throw new \RuntimeException('Clé OpenRouter manquante.');
        }

        $response = $this->httpClient->request('POST', $this->baseUrl . '/chat/completions', [
            'headers' => $this->headers(),
            'json' => [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 600,
                'stream' => true,
            ],
            'timeout' => 60,
        ]);

        $buffer = '';
        foreach ($this->httpClient->stream($response) as $chunk) {
            $buffer .= $chunk->getContent();

            while (false !== ($newlinePos = strpos($buffer, "\n"))) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ('' === $line || !str_starts_with($line, 'data:')) {
                    continue;
                }
                $data = trim(substr($line, 5));
                if ('[DONE]' === $data) {
                    return;
                }
                $decoded = json_decode($data, true);
                $content = $decoded['choices'][0]['delta']['content'] ?? null;
                if (null !== $content && '' !== $content) {
                    yield $content;
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => $this->appUrl,
            'X-Title' => $this->appName,
        ];
    }
}
