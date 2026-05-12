<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();

            $table->string('provider', 100)->default('the_odds_api');
            $table->string('endpoint', 255);

            $table->string('sport_key', 100)->nullable();
            $table->string('regions', 100)->nullable();
            $table->string('markets', 100)->nullable();

            $table->unsignedInteger('credits_used')->default(0);
            $table->unsignedInteger('requests_used')->nullable();
            $table->unsignedInteger('requests_remaining')->nullable();

            $table->unsignedSmallInteger('response_status')->nullable();

            $table->dateTime('requested_at');

            $table->timestamps();

            $table->index('provider');
            $table->index('sport_key');
            $table->index('requested_at');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
