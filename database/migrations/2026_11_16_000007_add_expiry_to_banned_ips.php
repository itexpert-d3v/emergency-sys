<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banned_ips', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('ip_address');
        });

        DB::table('banned_ips')->orderBy('id')->chunkById(500, function ($bans): void {
            foreach ($bans as $ban) {
                DB::table('banned_ips')->where('id', $ban->id)->update([
                    'expires_at' => \Illuminate\Support\Carbon::parse($ban->created_at ?? now())->addHours(24),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('banned_ips', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};