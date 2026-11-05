<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->boolean('is_excused')->default(false)->after('is_absent');
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('grade_scale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_percent', 5, 2);
            $table->decimal('max_percent', 5, 2);
            $table->string('grade', 10);
            $table->decimal('grade_point', 4, 2);
            $table->boolean('is_pass')->default(false);
            $table->timestamps();
            $table->unique(['grade_scale_id', 'grade']);
            $table->index(['grade_scale_id', 'min_percent', 'max_percent']);
        });

        Schema::create('subject_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->unsignedTinyInteger('credit_hours')->nullable();
            $table->enum('result_status', ['graded', 'excused', 'incomplete'])->default('incomplete');
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->unique(['term_id', 'class_id', 'student_id', 'subject_id'], 'subject_results_term_student_subject_unique');
            $table->index(['term_id', 'class_id', 'result_status'], 'subject_results_term_class_status_index');
        });

        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->enum('result_status', ['incomplete', 'pass', 'fail', 'excused'])->default('incomplete');
            $table->enum('publication_status', ['draft', 'pending_approval', 'published'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('unpublish_reason')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['term_id', 'class_id', 'student_id'], 'term_results_term_class_student_unique');
            $table->index(['term_id', 'class_id', 'publication_status'], 'term_results_term_class_publication_index');
        });

        Schema::create('term_result_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['queued', 'running', 'completed', 'failed']);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['term_id', 'class_id'], 'term_result_generations_term_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_results');
        Schema::dropIfExists('term_result_generations');
        Schema::dropIfExists('subject_results');
        Schema::dropIfExists('grade_scale_items');
        Schema::dropIfExists('grade_scales');

        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('is_excused');
        });
    }
};
