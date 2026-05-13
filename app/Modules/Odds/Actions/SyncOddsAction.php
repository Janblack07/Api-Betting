<?php

namespace App\Modules\Odds\Actions;

use App\Modules\Odds\Models\SportEvent;

class SyncOddsAction
{
    public function __construct(
        private readonly SyncEventOddsAction $syncEventOddsAction
    ) {
    }

    public function execute(array $filters = []): array
    {
        $events = SportEvent::query()
            ->where('is_active', true)
            ->whereIn('status', ['scheduled', 'live'])
            ->when($filters['event_id'] ?? null, fn ($query, $eventId) => $query->where('id', $eventId))
            ->when($filters['sport_key'] ?? null, fn ($query, $sportKey) => $query->where('sport_key', $sportKey))
            ->orderBy('commence_time')
            ->limit((int) ($filters['limit'] ?? 10))
            ->get();

        $summary = [
            'events_processed' => 0,
            'created' => 0,
            'unchanged' => 0,
            'deactivated' => 0,
            'details' => [],
        ];

        foreach ($events as $event) {
            $result = $this->syncEventOddsAction->execute(
                event: $event,
                regions: $filters['regions'] ?? null,
                markets: $filters['markets'] ?? null
            );

            $summary['events_processed']++;
            $summary['created'] += $result['created'];
            $summary['unchanged'] += $result['unchanged'];
            $summary['deactivated'] += $result['deactivated'];
            $summary['details'][] = $result;
        }

        return $summary;
    }
}
