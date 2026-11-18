<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('priority');
            $table->timestamp('accepted_at')->nullable()->after('status');
        });

        DB::table('incident_reports')->whereNull('accepted_at')->update([
            'accepted_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn(['remarks', 'accepted_at']);
        });
    }
};