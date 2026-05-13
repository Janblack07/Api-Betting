<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'odds.default_region',
                'setting_value' => 'eu',
                'type' => 'string',
                'description' => 'Región por defecto para consultar cuotas.',
            ],
            [
                'setting_key' => 'odds.default_market',
                'setting_value' => 'h2h',
                'type' => 'string',
                'description' => 'Mercado por defecto para consultar cuotas.',
            ],
            [
                'setting_key' => 'odds.sync_interval_seconds',
                'setting_value' => '120',
                'type' => 'integer',
                'description' => 'Intervalo de sincronización de cuotas en segundos.',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}
