<?php

namespace App\Modules\Betting\Actions;

use App\Modules\Admin\Events\AdminDashboardUpdated;
use App\Modules\Betting\Models\EventResult;
use App\Modules\Betting\Services\BetSettlementService;
use App\Modules\Odds\Clients\OddsApiClient;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Facades\DB;

class SyncEventResultsAction
{
    public function __construct(
        private readonly OddsApiClient $client,
        private readonly BetSettlementService $settlementService
    ) {
    }

    public function execute(string $sportKey, int $daysFrom = 3): array
    {
        $response = $this->client->scores(
            sportKey: $sportKey,
            daysFrom: $daysFrom
        );

        $items = $response['data'] ?? [];

        $summary = [
            'sport_key' => $sportKey,
            'days_from' => $daysFrom,
            'provider_events' => count($items),
            'matched_events' => 0,
            'completed_events' => 0,
            'results_created_or_updated' => 0,
            'settlement_triggered' => 0,
            'skipped' => [],
            'errors' => [],
            'api_usage' => $response['headers'] ?? [],
        ];

        foreach ($items as $item) {
            try {
                $externalEventId = $item['id'] ?? null;

                if (! $externalEventId) {
                    $summary['skipped'][] = [
                        'reason' => 'missing_external_event_id',
                        'payload' => $item,
                    ];

                    continue;
                }

                $event = SportEvent::query()
                    ->where('external_event_id', $externalEventId)
                    ->where('sport_key', $sportKey)
                    ->first();

                if (! $event) {
                    $summary['skipped'][] = [
                        'external_event_id' => $externalEventId,
                        'reason' => 'local_event_not_found',
                    ];

                    continue;
                }

                $summary['matched_events']++;

                $completed = (bool) ($item['completed'] ?? false);

                if (! $completed) {
                    $this->markEventLiveOrScheduled($event, $item);

                    $summary['skipped'][] = [
                        'event_id' => $event->id,
                        'external_event_id' => $externalEventId,
                        'reason' => 'event_not_completed',
                    ];

                    continue;
                }

                $scores = $this->extractScores(
                    payload: $item,
                    homeTeam: $event->home_team,
                    awayTeam: $event->away_team
                );

                if ($scores['home_score'] === null || $scores['away_score'] === null) {
                    $summary['skipped'][] = [
                        'event_id' => $event->id,
                        'external_event_id' => $externalEventId,
                        'reason' => 'scores_not_available',
                    ];

                    continue;
                }

                $resultType = $this->resolveResultType(
                    homeScore: $scores['home_score'],
                    awayScore: $scores['away_score']
                );

                $winnerName = $this->resolveWinnerName(
                    event: $event,
                    resultType: $resultType
                );

                DB::transaction(function () use ($event, $item, $scores, $resultType, $winnerName, &$summary) {
                    $event = SportEvent::query()
                        ->where('id', $event->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $result = EventResult::query()->updateOrCreate(
                        [
                            'sport_event_id' => $event->id,
                        ],
                        [
                            'external_event_id' => $event->external_event_id,
                            'sport_key' => $event->sport_key,
                            'home_score' => $scores['home_score'],
                            'away_score' => $scores['away_score'],
                            'winner_name' => $winnerName,
                            'result_type' => $resultType,
                            'status' => 'completed',
                            'source' => 'provider',
                            'raw_payload' => $item,
                            'resulted_at' => now(),
                        ]
                    );

                    $event->update([
                        'status' => 'completed',
                        'is_live' => false,
                        'is_active' => false,
                    ]);

                    $summary['completed_events']++;
                    $summary['results_created_or_updated']++;

                    $this->settlementService->settleBetsByEventResult($result);

                    $summary['settlement_triggered']++;
                });
            } catch (\Throwable $exception) {
                $summary['errors'][] = [
                    'external_event_id' => $item['id'] ?? null,
                    'message' => $exception->getMessage(),
                ];

                continue;
            }
        }

        event(new AdminDashboardUpdated('results.synced', [
            'sport_key' => $sportKey,
            'days_from' => $daysFrom,
            'provider_events' => $summary['provider_events'],
            'matched_events' => $summary['matched_events'],
            'completed_events' => $summary['completed_events'],
            'results_created_or_updated' => $summary['results_created_or_updated'],
            'settlement_triggered' => $summary['settlement_triggered'],
            'api_usage' => $summary['api_usage'],
        ]));

        return $summary;
    }

    private function extractScores(array $payload, string $homeTeam, string $awayTeam): array
    {
        $homeScore = null;
        $awayScore = null;

        foreach (($payload['scores'] ?? []) as $score) {
            $teamName = $score['name'] ?? null;
            $teamScore = isset($score['score']) ? (int) $score['score'] : null;

            if ($teamName === null || $teamScore === null) {
                continue;
            }

            if (strtolower(trim($teamName)) === strtolower(trim($homeTeam))) {
                $homeScore = $teamScore;
            }

            if (strtolower(trim($teamName)) === strtolower(trim($awayTeam))) {
                $awayScore = $teamScore;
            }
        }

        return [
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ];
    }

    private function resolveResultType(int $homeScore, int $awayScore): string
    {
        if ($homeScore > $awayScore) {
            return 'home';
        }

        if ($awayScore > $homeScore) {
            return 'away';
        }

        return 'draw';
    }

    private function resolveWinnerName(SportEvent $event, string $resultType): ?string
    {
        return match ($resultType) {
            'home' => $event->home_team,
            'away' => $event->away_team,
            'draw' => 'Draw',
            default => null,
        };
    }

    private function markEventLiveOrScheduled(SportEvent $event, array $payload): void
    {
        $hasScores = ! empty($payload['scores']);

        if ($hasScores && $event->status !== 'live') {
            $event->update([
                'status' => 'live',
                'is_live' => true,
                'is_active' => true,
            ]);

            return;
        }

        if (! $hasScores && $event->status !== 'scheduled') {
            $event->update([
                'status' => 'scheduled',
                'is_live' => false,
                'is_active' => true,
            ]);
        }
    }
}
