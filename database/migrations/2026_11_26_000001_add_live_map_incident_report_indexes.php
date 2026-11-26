<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'idx_live_status_created');
            $table->index(['accepted_at', 'status'], 'idx_live_accepted_status');
            $table->index('priority', 'idx_incident_priority');
        });
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->dropIndex('idx_live_status_created');
            $table->dropIndex('idx_live_accepted_status');
            $table->dropIndex('idx_incident_priority');
        });
    }
};
