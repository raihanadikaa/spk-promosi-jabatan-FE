<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis kriteria (benefit/cost) disimpan per periode, seperti bobot.
     * Sebelumnya hanya ada di tabel criteria (global), sehingga mengubah jenis di periode baru
     * ikut mengubah cara periode lama yang sudah ditutup ditampilkan.
     * criteria.attribute_type tetap ada sebagai jenis bawaan untuk periode baru.
     */
    public function up(): void
    {
        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->string('attribute_type', 10)->nullable()->after('weight_value');
        });

        // Isi dari jenis global yang berlaku saat ini.
        foreach (DB::table('criteria')->pluck('attribute_type', 'id') as $criteriaId => $type) {
            DB::table('criteria_weights')->where('criteria_id', $criteriaId)->update(['attribute_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('criteria_weights', function (Blueprint $table) {
            $table->dropColumn('attribute_type');
        });
    }
};
