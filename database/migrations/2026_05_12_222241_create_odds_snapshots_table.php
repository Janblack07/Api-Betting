<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odds_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sport_event_id')
                ->constrained('sport_events')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('external_event_id', 191);
            $table->string('sport_key', 100);

            $table->foreignId('bookmaker_id')
                ->nullable()
                ->constrained('bookmakers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('bookmaker_key', 100);
            $table->string('bookmaker_title', 150)->nullable();

            $table->foreignId('market_id')
                ->nullable()
                ->constrained('markets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('market_key', 50);

            $table->string('selection_name', 150);
            $table->string('selection_description', 255)->nullable();

            $table->decimal('price', 10, 4);
            $table->decimal('point', 10, 2)->nullable();

            $table->dateTime('commence_time')->nullable();
            $table->dateTime('snapshot_at');

            $table->string('hash', 64);
            $table->boolean('is_active')->default(true);

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index('sport_event_id');
            $table->index('external_event_id');
            $table->index('sport_key');
            $table->index('bookmaker_key');
            $table->index('market_key');
            $table->index('selection_name');
            $table->index('snapshot_at');
            $table->index('hash');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odds_snapshots');
    }
};
