<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // exam_id, exam_subject_id, class_subject_id and student_id are plain indexed columns
        // (no foreign keys) so this migration also works before Sumaiya's branch is merged.
        Schema::create('subject_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('exam_subject_id');
            $table->unsignedBigInteger('class_subject_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->unsignedSmallInteger('max_marks');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 2);
            $table->decimal('grade_point', 3, 1)->default(0);
            $table->boolean('is_pass')->default(false);
            $table->boolean('is_absent')->default(false);
            $table->timestamps();

            $table->unique(['exam_subject_id', 'student_id']);
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id')->index();
            $table->decimal('gpa', 4, 2)->default(0);
            $table->unsignedSmallInteger('subjects_total')->default(0);
            $table->unsignedSmallInteger('subjects_passed')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('subject_results');
    }
};
