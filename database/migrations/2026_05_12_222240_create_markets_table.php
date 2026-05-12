<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();

            $table->string('market_key', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
