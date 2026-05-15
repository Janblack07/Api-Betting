<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_usage_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('api_usage_logs', 'status_code')) {
                $table->unsignedSmallInteger('status_code')->nullable()->after('credits_used')->index();
            }

            if (! Schema::hasColumn('api_usage_logs', 'error_message')) {
                $table->text('error_message')->nullable()->after('status_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_usage_logs', function (Blueprint $table) {
            if (Schema::hasColumn('api_usage_logs', 'error_message')) {
                $table->dropColumn('error_message');
            }

            if (Schema::hasColumn('api_usage_logs', 'status_code')) {
                $table->dropColumn('status_code');
            }
        });
    }
};
