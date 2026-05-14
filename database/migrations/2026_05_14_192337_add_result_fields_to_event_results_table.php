<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            if (! Schema::hasColumn('event_results', 'external_event_id')) {
                $table->string('external_event_id')->nullable()->after('sport_event_id')->index();
            }

            if (! Schema::hasColumn('event_results', 'sport_key')) {
                $table->string('sport_key')->nullable()->after('external_event_id')->index();
            }

            if (! Schema::hasColumn('event_results', 'result_type')) {
                $table->enum('result_type', [
                    'home',
                    'away',
                    'draw',
                    'cancelled',
                    'unknown',
                ])->default('unknown')->after('winner_name');
            }

            if (! Schema::hasColumn('event_results', 'resulted_at')) {
                $table->timestamp('resulted_at')->nullable()->after('raw_payload');
            }
        });

        DB::statement("
            UPDATE event_results er
            INNER JOIN sport_events se ON se.id = er.sport_event_id
            SET
                er.external_event_id = se.external_event_id,
                er.sport_key = se.sport_key
            WHERE er.external_event_id IS NULL
               OR er.sport_key IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            if (Schema::hasColumn('event_results', 'resulted_at')) {
                $table->dropColumn('resulted_at');
            }

            if (Schema::hasColumn('event_results', 'result_type')) {
                $table->dropColumn('result_type');
            }

            if (Schema::hasColumn('event_results', 'sport_key')) {
                $table->dropColumn('sport_key');
            }

            if (Schema::hasColumn('event_results', 'external_event_id')) {
                $table->dropColumn('external_event_id');
            }
        });
    }
};
