<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subject')->restrictOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('class_subject_id', 'lms_courses_class_subject_unique');
        });

        Schema::create('lms_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $table->string('title', 160);
            $table->longText('content');
            $table->unsignedSmallInteger('position');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'position'], 'lms_lessons_course_position_unique');
        });

        Schema::create('lms_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('instructions');
            $table->timestamp('due_at')->nullable();
            $table->decimal('max_points', 7, 2);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['course_id', 'published_at']);
        });

        Schema::create('lms_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('lms_assignments')->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->longText('body');
            $table->timestamp('submitted_at');
            $table->decimal('score', 7, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id'], 'lms_submission_assignment_student_unique');
            $table->index(['student_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_submissions');
        Schema::dropIfExists('lms_assignments');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_courses');
    }
};
