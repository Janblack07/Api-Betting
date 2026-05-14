<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            if (! Schema::hasColumn('bets', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('status');
            }

            if (! Schema::hasColumn('bets', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('settled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            if (Schema::hasColumn('bets', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }

            if (Schema::hasColumn('bets', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
