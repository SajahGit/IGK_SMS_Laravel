<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained()->restrictOnDelete();
            $table->date('session_date');
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open'); // open | submitted
            $table->timestamps();

            $table->unique(['class_subject_id', 'session_date']);
            $table->index('session_date');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('present'); // present | absent | late | excused
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
