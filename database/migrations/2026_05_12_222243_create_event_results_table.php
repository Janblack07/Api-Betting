<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sport_event_id')
                ->unique()
                ->constrained('sport_events')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            $table->string('winner_name', 150)->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->enum('source', [
                'provider',
                'manual',
            ])->default('provider');

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_results');
    }
};
