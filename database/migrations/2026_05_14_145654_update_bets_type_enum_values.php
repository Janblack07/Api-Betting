<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE bets
            MODIFY type ENUM('single', 'combo')
            NOT NULL DEFAULT 'single'
        ");

        DB::statement("
            ALTER TABLE bets
            MODIFY status ENUM('pending', 'accepted', 'won', 'lost', 'cancelled', 'rejected', 'refunded')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE bets
            MODIFY type ENUM('simple', 'combined')
            NOT NULL DEFAULT 'simple'
        ");

        DB::statement("
            ALTER TABLE bets
            MODIFY status ENUM('pending', 'won', 'lost', 'refunded')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
