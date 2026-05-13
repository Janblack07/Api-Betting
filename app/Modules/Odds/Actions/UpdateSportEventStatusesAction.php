<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Admin\Models\ApiUsageLog;
use App\Modules\Odds\Clients\OddsApiClient;
use App\Modules\Odds\Events\SportEventStatusUpdated;
use App\Modules\Odds\Models\Sport;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Facades\DB;

class UpdateSportEventStatusesAction
{
    public function __construct(
        private readonly OddsApiClient $client
    ) {
    }

    public function execute(?string $sportKey = null): array
    {
        $summary = [
            'marked_live' => 0,
            'marked_completed' => 0,
            'sports_processed' => 0,
        ];


        $scheduledEvents = SportEvent::query()
            ->where('status', 'scheduled')
            ->where('commence_time', '<=', now())
            ->get();

        foreach ($scheduledEvents as $event) {
            $event->update([
                'status' => 'live',
                'is_live' => true,
            ]);

            $summary['marked_live']++;

            event(new SportEventStatusUpdated(
                sportKey: $event->sport_key,
                eventId: $event->id,
                externalEventId: $event->external_event_id,
                status: $event->status,
                isLive: (bool) $event->is_live,
                isActive: (bool) $event->is_active
            ));
        }


        $sports = Sport::query()
            ->where('active', true)
            ->when($sportKey, function ($query, string $sportKey) {
                $query->where('sport_key', $sportKey);
            })
            ->get();

        foreach ($sports as $sport) {
            $result = $this->client->getScoresBySport($sport->sport_key, 1);

            $scores = $result['data'] ?? [];
            $headers = $result['headers'] ?? [];
            $status = $result['status'] ?? 200;

            DB::transaction(function () use ($sport, $scores, $headers, $status, &$summary) {
                foreach ($scores as $scorePayload) {
                    if (! isset($scorePayload['id'])) {
                        continue;
                    }

                    if (($scorePayload['completed'] ?? false) !== true) {
                        continue;
                    }

                    $events = SportEvent::query()
                        ->where('external_event_id', $scorePayload['id'])
                        ->whereIn('status', ['scheduled', 'live'])
                        ->get();

                    foreach ($events as $event) {
                        $event->update([
                            'status' => 'completed',
                            'is_live' => false,
                            'is_active' => false,
                            'raw_payload' => $scorePayload,
                        ]);

                        $summary['marked_completed']++;

                        event(new SportEventStatusUpdated(
                            sportKey: $event->sport_key,
                            eventId: $event->id,
                            externalEventId: $event->external_event_id,
                            status: $event->status,
                            isLive: (bool) $event->is_live,
                            isActive: (bool) $event->is_active
                        ));
                    }
                }

                ApiUsageLog::query()->create([
                    'provider' => 'the_odds_api',
                    'endpoint' => "/sports/{$sport->sport_key}/scores",
                    'sport_key' => $sport->sport_key,
                    'regions' => null,
                    'markets' => null,
                    'credits_used' => (int) ($headers['requests_last'] ?? 0),
                    'requests_used' => isset($headers['requests_used'])
                        ? (int) $headers['requests_used']
                        : null,
                    'requests_remaining' => isset($headers['requests_remaining'])
                        ? (int) $headers['requests_remaining']
                        : null,
                    'response_status' => $status,
                    'requested_at' => now(),
                ]);
            });

            $summary['sports_processed']++;
        }

        return $summary;
    }
}
