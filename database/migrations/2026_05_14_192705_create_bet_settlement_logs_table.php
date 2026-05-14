<?php

use App\Models\User;
use App\Modules\Betting\Models\Bet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bet_settlement_logs')) {
            Schema::create('bet_settlement_logs', function (Blueprint $table) {
                $table->id();

                $table->foreignIdFor(Bet::class)
                    ->constrained('bets')
                    ->cascadeOnDelete();

                $table->foreignIdFor(User::class, 'admin_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->enum('settlement_type', [
                    'won',
                    'lost',
                    'refunded',
                ]);

                $table->enum('source', [
                    'automatic',
                    'manual',
                ])->default('automatic');

                $table->string('previous_status')->nullable();
                $table->string('new_status');
                $table->text('observation')->nullable();
                $table->json('payload')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_settlement_logs');
    }
};
