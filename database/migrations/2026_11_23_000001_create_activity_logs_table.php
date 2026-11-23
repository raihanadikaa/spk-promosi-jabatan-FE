<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak aktivitas: siapa melakukan apa dan kapan (ubah bobot, kirim nilai, hitung SAW, keputusan direktur, dll).
     * Hanya ditambah, tidak pernah diubah, supaya bisa dipakai menelusuri asal sebuah keputusan promosi.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 120)->nullable(); // disimpan juga, supaya tetap terbaca bila akun dihapus
            $table->string('category', 30);
            $table->string('action', 60);
            $table->string('description', 500);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('period_id')->nullable()->constrained('assessment_periods')->nullOnDelete();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['category', 'created_at']);
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
