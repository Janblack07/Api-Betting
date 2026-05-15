<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncOddsAction
{
    public function __construct(
        private readonly SyncEventOddsAction $syncEventOddsAction
    ) {
    }

    public function execute(array $filters = []): array
    {
        $sportKey = $filters['sport_key'] ?? 'all';
        $eventId = $filters['event_id'] ?? null;
        $regions = $filters['regions'] ?? 'default';
        $markets = $filters['markets'] ?? 'default';

        $lockKey = 'locks:odds-sync:'
            . $sportKey . ':'
            . ($eventId ?? 'all') . ':'
            . $regions . ':'
            . $markets;

        $lock = Cache::lock($lockKey, 300);

        if (! $lock->get()) {
            return [
                'locked' => true,
                'message' => 'Ya existe una sincronización de cuotas en ejecución para estos parámetros.',
                'lock_key' => $lockKey,
            ];
        }

        try {
            return $this->executeSync($filters);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'locked' => false,
                'success' => false,
                'message' => 'Error durante la sincronización de cuotas.',
                'error' => $exception->getMessage(),
                'lock_key' => $lockKey,
            ];
        } finally {
            optional($lock)->release();
        }
    }

    private function executeSync(array $filters = []): array
    {
        $events = SportEvent::query()
            ->where('is_active', true)
            ->whereIn('status', ['scheduled', 'live'])
            ->when(
                $filters['event_id'] ?? null,
                fn ($query, $eventId) => $query->where('id', $eventId)
            )
            ->when(
                $filters['sport_key'] ?? null,
                fn ($query, $sportKey) => $query->where('sport_key', $sportKey)
            )
            ->orderBy('commence_time')
            ->limit((int) ($filters['limit'] ?? 10))
            ->get();

        $summary = [
            'locked' => false,
            'events_found' => $events->count(),
            'events_processed' => 0,
            'created' => 0,
            'unchanged' => 0,
            'deactivated' => 0,
            'details' => [],
            'errors' => [],
        ];

        foreach ($events as $event) {
            try {
                $result = $this->syncEventOddsAction->execute(
                    event: $event,
                    regions: $filters['regions'] ?? null,
                    markets: $filters['markets'] ?? null
                );

                $summary['events_processed']++;
                $summary['created'] += (int) ($result['created'] ?? 0);
                $summary['unchanged'] += (int) ($result['unchanged'] ?? 0);
                $summary['deactivated'] += (int) ($result['deactivated'] ?? 0);
                $summary['details'][] = $result;
            } catch (Throwable $exception) {
                report($exception);

                $summary['errors'][] = [
                    'event_id' => $event->id,
                    'external_event_id' => $event->external_event_id,
                    'sport_key' => $event->sport_key,
                    'message' => $exception->getMessage(),
                ];

                continue;
            }
        }

        return $summary;
    }
}
