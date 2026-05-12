<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_events', function (Blueprint $table) {
            $table->id();

            $table->string('external_event_id', 191)->unique();

            $table->foreignId('sport_id')
                ->constrained('sports')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('sport_key', 100);

            $table->string('home_team', 150);
            $table->string('away_team', 150);

            $table->dateTime('commence_time');

            $table->enum('status', [
                'scheduled',
                'live',
                'completed',
                'cancelled',
                'suspended',
                'unavailable',
            ])->default('scheduled');

            $table->boolean('is_live')->default(false);
            $table->boolean('is_active')->default(true);

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index('sport_id');
            $table->index('sport_key');
            $table->index('commence_time');
            $table->index('status');
            $table->index('is_live');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_events');
    }
};
