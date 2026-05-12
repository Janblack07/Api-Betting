<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('code', 50)->unique();

            $table->enum('type', [
                'simple',
                'combined',
            ])->default('simple');

            $table->decimal('total_amount', 12, 2);
            $table->decimal('total_odds', 12, 4);
            $table->decimal('potential_win', 12, 2);

            $table->enum('status', [
                'pending',
                'accepted',
                'won',
                'lost',
                'cancelled',
                'refunded',
                'rejected',
            ])->default('pending');

            $table->dateTime('placed_at');
            $table->dateTime('settled_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('code');
            $table->index('status');
            $table->index('placed_at');
            $table->index('settled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
