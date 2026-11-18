<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
        });

        DB::table('users')->orderBy('id')->get(['id', 'name'])->each(function (object $user): void {
            $base = Str::limit(Str::slug($user->name, '_'), 32, '');
            $base = $base !== '' ? $base : 'user';
            $username = $base;
            $suffix = 2;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = Str::limit($base, 32, '') . '_' . $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};