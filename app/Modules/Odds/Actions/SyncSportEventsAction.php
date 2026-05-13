<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Admin\Models\ApiUsageLog;
use App\Modules\Odds\Clients\OddsApiClient;
use App\Modules\Odds\Models\Sport;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncSportEventsAction
{
    public function __construct(
        private readonly OddsApiClient $client
    ) {
    }

    public function execute(?string $sportKey = null): array
    {
        $sports = Sport::query()
            ->where('active', true)
            ->when($sportKey, function ($query, string $sportKey) {
                $query->where('sport_key', $sportKey);
            })
            ->get();

        $summary = [
            'sports_processed' => 0,
            'events_received' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        foreach ($sports as $sport) {
            $result = $this->client->getEventsBySport($sport->sport_key);

            $events = $result['data'] ?? [];
            $headers = $result['headers'] ?? [];
            $status = $result['status'] ?? 200;

            DB::transaction(function () use ($sport, $events, $headers, $status, &$summary) {
                foreach ($events as $item) {
                    if (! isset($item['id'], $item['home_team'], $item['away_team'], $item['commence_time'])) {
                        continue;
                    }

                    $commenceTime = Carbon::parse($item['commence_time']);

                    $event = SportEvent::query()->updateOrCreate(
                        [
                            'external_event_id' => $item['id'],
                        ],
                        [
                            'sport_id' => $sport->id,
                            'sport_key' => $sport->sport_key,
                            'home_team' => $item['home_team'],
                            'away_team' => $item['away_team'],
                            'commence_time' => $commenceTime,
                            'status' => $commenceTime->isPast() ? 'live' : 'scheduled',
                            'is_live' => $commenceTime->isPast(),
                            'is_active' => true,
                            'raw_payload' => $item,
                        ]
                    );

                    if ($event->wasRecentlyCreated) {
                        $summary['created']++;
                    } else {
                        $summary['updated']++;
                    }
                }

                ApiUsageLog::query()->create([
                    'provider' => 'the_odds_api',
                    'endpoint' => "/sports/{$sport->sport_key}/events",
                    'sport_key' => $sport->sport_key,
                    'regions' => null,
                    'markets' => null,
                    'credits_used' => (int) ($headers['requests_last'] ?? 0),
                    'requests_used' => isset($headers['requests_used']) ? (int) $headers['requests_used'] : null,
                    'requests_remaining' => isset($headers['requests_remaining']) ? (int) $headers['requests_remaining'] : null,
                    'response_status' => $status,
                    'requested_at' => now(),
                ]);
            });

            $summary['sports_processed']++;
            $summary['events_received'] += count($events);
        }

        return $summary;
    }
}
