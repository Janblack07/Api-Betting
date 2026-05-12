<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('locked_balance', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');

            $table->timestamps();

            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
