<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Admin\Models\ApiUsageLog;
use App\Modules\Odds\Clients\OddsApiClient;
use App\Modules\Odds\Models\Sport;
use Illuminate\Support\Facades\DB;

class SyncSportsAction
{
    public function __construct(
        private readonly OddsApiClient $client
    ) {
    }

    public function execute(): array
    {
        $result = $this->client->getSports();

        $sports = $result['data'] ?? [];
        $headers = $result['headers'] ?? [];
        $status = $result['status'] ?? 200;

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($sports, $headers, $status, &$created, &$updated) {
            foreach ($sports as $item) {
                if (! isset($item['key'])) {
                    continue;
                }

                $sport = Sport::query()->updateOrCreate(
                    [
                        'sport_key' => $item['key'],
                    ],
                    [
                        'group' => $item['group'] ?? null,
                        'title' => $item['title'] ?? $item['key'],
                        'description' => $item['description'] ?? null,
                        'active' => (bool) ($item['active'] ?? true),
                        'has_outrights' => (bool) ($item['has_outrights'] ?? false),
                    ]
                );

                if ($sport->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            ApiUsageLog::query()->create([
                'provider' => 'the_odds_api',
                'endpoint' => '/sports',
                'sport_key' => null,
                'regions' => null,
                'markets' => null,
                'credits_used' => (int) ($headers['requests_last'] ?? 0),
                'requests_used' => isset($headers['requests_used']) ? (int) $headers['requests_used'] : null,
                'requests_remaining' => isset($headers['requests_remaining']) ? (int) $headers['requests_remaining'] : null,
                'response_status' => $status,
                'requested_at' => now(),
            ]);
        });

        return [
            'total_received' => count($sports),
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
