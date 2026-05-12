<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmakers', function (Blueprint $table) {
            $table->id();

            $table->string('bookmaker_key', 100)->unique();
            $table->string('title', 150);
            $table->string('region', 20)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('region');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmakers');
    }
};
