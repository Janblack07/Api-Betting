<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 80)
                    ->default('system')
                    ->after('user_id')
                    ->index();
            }

            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')
                    ->nullable()
                    ->after('new_values');
            }

            if (! Schema::hasColumn('audit_logs', 'performed_at')) {
                $table->timestamp('performed_at')
                    ->nullable()
                    ->after('user_agent')
                    ->index();
            }
        });

        DB::table('audit_logs')
            ->whereNull('performed_at')
            ->update([
                'performed_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'performed_at')) {
                $table->dropColumn('performed_at');
            }

            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('audit_logs', 'module')) {
                $table->dropColumn('module');
            }
        });
    }
};
