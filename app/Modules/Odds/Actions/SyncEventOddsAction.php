<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Admin\Models\ApiUsageLog;
use App\Modules\Odds\Clients\OddsApiClient;
use App\Modules\Odds\Models\Bookmaker;
use App\Modules\Odds\Models\Market;
use App\Modules\Odds\Models\OddsSnapshot;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Admin\Services\SystemSettingService;
use App\Modules\Odds\Events\OddsUpdated;

class SyncEventOddsAction
{
    public function __construct(
    private readonly OddsApiClient $client,
    private readonly SystemSettingService $systemSettingService
) {
}

    public function execute(
        SportEvent $event,
        ?string $regions = null,
        ?string $markets = null
    ): array {
        $regions = $regions ?: $this->systemSettingService->get(
            'odds.default_region',
            config('services.odds_api.default_region', 'eu')
        );

        $markets = $markets ?: $this->systemSettingService->get(
            'odds.default_market',
            config('services.odds_api.default_market', 'h2h')
        );
        $result = $this->client->getEventOdds(
            sportKey: $event->sport_key,
            eventId: $event->external_event_id,
            regions: $regions,
            markets: $markets
        );

        $data = $result['data'] ?? [];
        $headers = $result['headers'] ?? [];
        $status = $result['status'] ?? 200;

        $summary = [
            'event_id' => $event->id,
            'external_event_id' => $event->external_event_id,
            'created' => 0,
            'unchanged' => 0,
            'deactivated' => 0,
        ];

        DB::transaction(function () use ($event, $data, $headers, $status, $regions, $markets, &$summary) {
            $bookmakers = $data['bookmakers'] ?? [];

            foreach ($bookmakers as $bookmakerPayload) {
                $bookmaker = Bookmaker::query()->updateOrCreate(
                    ['bookmaker_key' => $bookmakerPayload['key']],
                    [
                        'title' => $bookmakerPayload['title'] ?? $bookmakerPayload['key'],
                        'region' => $bookmakerPayload['region'] ?? null,
                        'active' => true,
                    ]
                );

                foreach (($bookmakerPayload['markets'] ?? []) as $marketPayload) {
                    $market = Market::query()->updateOrCreate(
                        ['market_key' => $marketPayload['key']],
                        [
                            'name' => strtoupper($marketPayload['key']),
                            'description' => null,
                            'active' => true,
                        ]
                    );

                    foreach (($marketPayload['outcomes'] ?? []) as $outcomePayload) {
                        $point = $outcomePayload['point'] ?? null;

                        $hash = $this->makeHash(
                            eventId: $event->id,
                            bookmakerKey: $bookmaker->bookmaker_key,
                            marketKey: $market->market_key,
                            selectionName: $outcomePayload['name'],
                            price: $outcomePayload['price'],
                            point: $point
                        );

                        $latest = OddsSnapshot::query()
                            ->where('sport_event_id', $event->id)
                            ->where('bookmaker_key', $bookmaker->bookmaker_key)
                            ->where('market_key', $market->market_key)
                            ->where('selection_name', $outcomePayload['name'])
                            ->where(function ($query) use ($point) {
                                is_null($point)
                                    ? $query->whereNull('point')
                                    : $query->where('point', $point);
                            })
                            ->where('is_active', true)
                            ->latest('snapshot_at')
                            ->first();

                        if ($latest && $latest->hash === $hash) {
                            $summary['unchanged']++;
                            continue;
                        }

                        if ($latest) {
                            $latest->update(['is_active' => false]);
                            $summary['deactivated']++;
                        }

                        OddsSnapshot::query()->create([
                            'sport_event_id' => $event->id,
                            'external_event_id' => $event->external_event_id,
                            'sport_key' => $event->sport_key,
                            'bookmaker_id' => $bookmaker->id,
                            'bookmaker_key' => $bookmaker->bookmaker_key,
                            'bookmaker_title' => $bookmaker->title,
                            'market_id' => $market->id,
                            'market_key' => $market->market_key,
                            'selection_name' => $outcomePayload['name'],
                            'selection_description' => $outcomePayload['description'] ?? null,
                            'price' => $outcomePayload['price'],
                            'point' => $point,
                            'commence_time' => isset($data['commence_time'])
                                ? Carbon::parse($data['commence_time'])
                                : $event->commence_time,
                            'snapshot_at' => now(),
                            'hash' => $hash,
                            'is_active' => true,
                            'raw_payload' => [
                                'bookmaker' => $bookmakerPayload,
                                'market' => $marketPayload,
                                'outcome' => $outcomePayload,
                            ],
                        ]);

                        $summary['created']++;
                    }
                }
            }

            ApiUsageLog::query()->create([
                'provider' => 'the_odds_api',
                'endpoint' => "/sports/{$event->sport_key}/events/{$event->external_event_id}/odds",
                'sport_key' => $event->sport_key,
                'regions' => $regions ?: config('services.odds_api.default_region', 'eu'),
                'markets' => $markets ?: config('services.odds_api.default_market', 'h2h'),
                'credits_used' => (int) ($headers['requests_last'] ?? 0),
                'requests_used' => isset($headers['requests_used']) ? (int) $headers['requests_used'] : null,
                'requests_remaining' => isset($headers['requests_remaining']) ? (int) $headers['requests_remaining'] : null,
                'response_status' => $status,
                'requested_at' => now(),
            ]);
        });
        if ($summary['created'] > 0 || $summary['deactivated'] > 0) {
            event(new OddsUpdated(
                sportKey: $event->sport_key,
                eventId: $event->id,
                externalEventId: $event->external_event_id,
                summary: $summary
            ));
        }

        return $summary;
    }

    private function makeHash(
        int $eventId,
        string $bookmakerKey,
        string $marketKey,
        string $selectionName,
        float|int|string $price,
        float|int|string|null $point
    ): string {
        return hash('sha256', implode('|', [
            $eventId,
            $bookmakerKey,
            $marketKey,
            $selectionName,
            $price,
            $point ?? 'null',
        ]));
    }
}
