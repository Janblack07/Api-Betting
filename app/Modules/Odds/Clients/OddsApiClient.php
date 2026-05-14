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

        return $this->formatResponse($response);
    }

    public function getEventsBySport(string $sportKey): array
    {
        $response = $this->request("/sports/{$sportKey}/events", [
            'dateFormat' => config('services.odds_api.default_date_format', 'iso'),
        ]);

        return $this->formatResponse($response);
    }
    public function scores(string $sportKey, int $daysFrom = 3): array
{
    $response = Http::timeout((int) config('services.odds_api.timeout', 15))
        ->get(rtrim(config('services.odds_api.base_url'), '/') . "/sports/{$sportKey}/scores", [
            'apiKey' => config('services.odds_api.key'),
            'daysFrom' => $daysFrom,
            'dateFormat' => config('services.odds_api.date_format', 'iso'),
        ]);

    if ($response->failed()) {
        throw new \RuntimeException(
            'Error al consultar scores de The Odds API: ' . $response->body()
        );
    }

    return [
        'data' => $response->json(),
        'headers' => [
            'requests_remaining' => $response->header('x-requests-remaining'),
            'requests_used' => $response->header('x-requests-used'),
            'requests_last' => $response->header('x-requests-last'),
        ],
    ];
}

    public function getEventOdds(
        string $sportKey,
        string $eventId,
        ?string $regions = null,
        ?string $markets = null,
        ?string $oddsFormat = null
    ): array {
        $response = $this->request("/sports/{$sportKey}/events/{$eventId}/odds", [
            'regions' => $regions ?: config('services.odds_api.default_region', 'eu'),
            'markets' => $markets ?: config('services.odds_api.default_market', 'h2h'),
            'oddsFormat' => $oddsFormat ?: config('services.odds_api.default_format', 'decimal'),
            'dateFormat' => config('services.odds_api.default_date_format', 'iso'),
        ]);

        return $this->formatResponse($response);
    }

    public function getScoresBySport(string $sportKey, int $daysFrom = 1): array
    {
        $response = $this->request("/sports/{$sportKey}/scores", [
            'daysFrom' => $daysFrom,
            'dateFormat' => config('services.odds_api.default_date_format', 'iso'),
        ]);

        return $this->formatResponse($response);
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

    private function formatResponse(Response $response): array
    {
        return [
            'data' => $response->json() ?? [],
            'headers' => $this->usageHeaders($response),
            'status' => $response->status(),
        ];
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
