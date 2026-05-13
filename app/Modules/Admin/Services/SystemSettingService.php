<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection;

class SystemSettingService
{
    public function all(): Collection
    {
        return SystemSetting::query()
            ->orderBy('setting_key')
            ->get();
    }

    public function updateMany(array $settings): Collection
    {
        foreach ($settings as $key => $value) {
            $type = $this->detectType($key, $value);

            SystemSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $type,
                    'description' => $this->descriptionFor($key),
                ]
            );
        }

        return $this->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SystemSetting::query()
            ->where('setting_key', $key)
            ->first();

        return $setting?->typed_value ?? $default;
    }

    private function detectType(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'decimal';
        }

        if (is_array($value)) {
            return 'json';
        }

        return match ($key) {
            'odds.sync_interval_seconds' => 'integer',
            default => 'string',
        };
    }

    private function descriptionFor(string $key): ?string
    {
        return match ($key) {
            'odds.default_region' => 'Región por defecto para consultar cuotas.',
            'odds.default_market' => 'Mercado por defecto para consultar cuotas.',
            'odds.sync_interval_seconds' => 'Intervalo de sincronización de cuotas en segundos.',
            default => null,
        };
    }
}
