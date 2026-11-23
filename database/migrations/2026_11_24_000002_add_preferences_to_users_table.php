<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferensi tampilan per pengguna, disimpan di akun supaya sama di setiap perangkat & setiap login:
 * bahasa (id/en), tema (light/dark/system), ukuran teks (sm/md/lg). Null = bawaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('avatar_preset');
            $table->string('theme', 10)->nullable()->after('locale');
            $table->string('text_size', 2)->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'theme', 'text_size']);
        });
    }
};
