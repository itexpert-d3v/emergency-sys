<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->string('rejection_reason', 500)->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });
    }
};