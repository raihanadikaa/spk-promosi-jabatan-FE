<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Lima kriteria SAW yang disepakati kelompok (proposal + masukan studi kasus).
     * Bagian dari skema karena logika aplikasi bergantung pada kriteria ini; bobot diatur HRD per periode.
     */
    public function up(): void
    {
        $now = now();

        DB::table('criteria')->insert([
            ['code' => 'C1', 'criteria_name' => 'Masa kerja', 'attribute_type' => 'benefit', 'input_type' => 'auto', 'unit' => 'tahun',
                'min_value' => null, 'max_value' => null, 'sort_order' => 1,
                'description' => 'Dihitung otomatis dari tanggal bergabung sampai akhir periode.'],
            ['code' => 'C2', 'criteria_name' => 'Kinerja', 'attribute_type' => 'benefit', 'input_type' => 'score', 'unit' => 'skor 0–100',
                'min_value' => 0, 'max_value' => 100, 'sort_order' => 2,
                'description' => 'Pencapaian target kerja dan hasil project selama periode.'],
            ['code' => 'C3', 'criteria_name' => 'Kompetensi', 'attribute_type' => 'benefit', 'input_type' => 'score', 'unit' => 'skor 0–100',
                'min_value' => 0, 'max_value' => 100, 'sort_order' => 3,
                'description' => 'Keahlian teknis, kepemimpinan, dan komunikasi.'],
            ['code' => 'C4', 'criteria_name' => 'Kedisiplinan', 'attribute_type' => 'cost', 'input_type' => 'count', 'unit' => 'hari',
                'min_value' => 0, 'max_value' => 60, 'sort_order' => 4,
                'description' => 'Jumlah hari terlambat atau absen tanpa keterangan. Makin kecil makin baik.'],
            ['code' => 'C5', 'criteria_name' => 'Pelatihan & sertifikasi', 'attribute_type' => 'benefit', 'input_type' => 'count', 'unit' => 'sertifikat',
                'min_value' => 0, 'max_value' => 20, 'sort_order' => 5,
                'description' => 'Jumlah pelatihan atau sertifikasi yang diselesaikan dan relevan dengan jabatan.'],
        ]);

        DB::table('criteria')->update(['created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('criteria')->whereIn('code', ['C1', 'C2', 'C3', 'C4', 'C5'])->delete();
    }
};
