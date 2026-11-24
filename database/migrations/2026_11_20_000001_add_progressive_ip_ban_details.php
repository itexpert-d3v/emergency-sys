<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banned_ips', function (Blueprint $table) {
            $table->string('reason')->nullable();
            $table->unsignedInteger('violation_count')->default(0);
            $table->char('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('asn')->nullable();
            $table->string('isp')->nullable();
            $table->timestamp('banned_at')->nullable();
        });

        Schema::create('rate_limit_violations', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('profile', 64);
            $table->unsignedInteger('violation_count');
            $table->unsignedInteger('request_count');
            $table->unsignedInteger('window_seconds');
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('banned_until')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('asn')->nullable();
            $table->string('isp')->nullable();
            $table->timestamps();
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_violations');

        Schema::table('banned_ips', function (Blueprint $table) {
            $table->dropColumn([
                'reason',
                'violation_count',
                'country_code',
                'region',
                'city',
                'asn',
                'isp',
                'banned_at',
            ]);
        });
    }
};
