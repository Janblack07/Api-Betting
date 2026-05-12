<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_selections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bet_id')
                ->constrained('bets')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sport_event_id')
                ->constrained('sport_events')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('snapshot_id')
                ->constrained('odds_snapshots')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('external_event_id', 191);
            $table->string('sport_key', 100);
            $table->string('market_key', 50);
            $table->string('bookmaker_key', 100);

            $table->string('selection_name', 150);
            $table->decimal('odds_price', 10, 4);
            $table->decimal('point', 10, 2)->nullable();

            $table->enum('status', [
                'pending',
                'won',
                'lost',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->string('result', 100)->nullable();

            $table->timestamps();

            $table->index('bet_id');
            $table->index('sport_event_id');
            $table->index('snapshot_id');
            $table->index('status');
            $table->index('sport_key');
            $table->index('market_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_selections');
    }
};
