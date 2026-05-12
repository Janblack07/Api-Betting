<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('type', 100);
            $table->string('title', 150);
            $table->text('message')->nullable();

            $table->json('data')->nullable();

            $table->dateTime('read_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
