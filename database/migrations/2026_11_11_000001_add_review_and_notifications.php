<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Status keputusan direktur bertambah 'revision' (minta review ulang ke HRD).
        // Diubah dari enum ke string supaya status baru tidak perlu ALTER ENUM lagi.
        Schema::table('ranking_results', function (Blueprint $table) {
            $table->string('approval_status', 20)->nullable()->change();
        });

        // Notifikasi di aplikasi (pengingat penilaian dari HRD ke atasan, dsb).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('ranking_results', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable()->change();
        });
    }
};
