<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();

            $table->string('sport_key', 100)->unique();
            $table->string('group', 100)->nullable();
            $table->string('title', 150);
            $table->string('description', 255)->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('has_outrights')->default(false);

            $table->timestamps();

            $table->index('active');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
