<?php

namespace App\Modules\Odds\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OddsApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.odds_api.base_url'), '/');
        $this->apiKey = (string) config('services.odds_api.key');
        $this->timeout = (int) config('services.odds_api.timeout', 15);

        if (blank($this->apiKey)) {
            throw new RuntimeException('La API key de The Odds API no está configurada.');
        }
    }

    public function getSports(): array
    {
        $response = $this->request('/sports', [
            'all' => 'true',
        ]);

        return [
            'data' => $response->json() ?? [],
            'headers' => $this->usageHeaders($response),
            'status' => $response->status(),
        ];
    }

    public function getEventsBySport(string $sportKey): array
    {
        $response = $this->request("/sports/{$sportKey}/events", [
            'dateFormat' => 'iso',
        ]);

        return [
            'data' => $response->json() ?? [],
            'headers' => $this->usageHeaders($response),
            'status' => $response->status(),
        ];
    }

    private function request(string $uri, array $query = []): Response
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl . $uri, array_merge($query, [
                    'apiKey' => $this->apiKey,
                ]));

            if ($response->failed()) {
                throw new RuntimeException(
                    'Error al consultar The Odds API: ' . $response->body(),
                    $response->status()
                );
            }

            return $response;
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'No se pudo conectar con The Odds API: ' . $exception->getMessage()
            );
        }
    }

    private function usageHeaders(Response $response): array
    {
        return [
            'requests_remaining' => $response->header('x-requests-remaining'),
            'requests_used' => $response->header('x-requests-used'),
            'requests_last' => $response->header('x-requests-last'),
        ];
    }
}
