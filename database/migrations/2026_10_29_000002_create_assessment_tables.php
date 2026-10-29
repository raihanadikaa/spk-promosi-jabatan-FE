<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periode, kriteria & bobot, penilaian, normalisasi, dan hasil ranking SAW.
     */
    public function up(): void
    {
        Schema::create('assessment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_name', 60)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('assessment_deadline');
            $table->enum('status', ['draft', 'ongoing', 'closed'])->default('draft')->index();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('criteria_name', 60);
            $table->enum('attribute_type', ['benefit', 'cost']);
            // auto = dihitung sistem (masa kerja), score = skor 0–100, count = jumlah kejadian
            $table->enum('input_type', ['auto', 'score', 'count']);
            $table->string('unit', 30);
            $table->unsignedSmallInteger('min_value')->nullable();
            $table->unsignedSmallInteger('max_value')->nullable();
            $table->string('description');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('criteria_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->unsignedTinyInteger('weight_value'); // persen; total per periode = 100
            $table->timestamps();

            $table->unique(['period_id', 'criteria_id']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'employee_id']);
            $table->index(['period_id', 'status']);
        });

        Schema::create('assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->restrictOnDelete();
            $table->decimal('raw_score', 8, 2);
            $table->timestamps();

            $table->unique(['assessment_id', 'criteria_id']);
        });

        Schema::create('normalized_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('normalized_value', 8, 4); // r = x/max (benefit) atau min/x (cost)
            $table->decimal('weighted_value', 8, 4);   // w · r
            $table->timestamps();
        });

        Schema::create('ranking_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->decimal('final_score', 8, 4); // V = Σ wj·rij
            $table->unsignedSmallInteger('rank');
            $table->boolean('is_recommended')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'employee_id']);
            $table->index(['period_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_results');
        Schema::dropIfExists('normalized_scores');
        Schema::dropIfExists('assessment_details');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('criteria_weights');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('assessment_periods');
    }
};
