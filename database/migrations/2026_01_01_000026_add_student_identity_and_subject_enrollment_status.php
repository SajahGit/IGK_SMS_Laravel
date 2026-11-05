<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('national_id', 30)->nullable()->unique();
        });

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->enum('status', ['enrolled', 'dropped', 'completed'])->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['class_subject_id', 'status'], 'student_subject_enrollment_status_index');
        });

        DB::table('student_subject_enrollments')
            ->whereNull('enrolled_at')
            ->update(['enrolled_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropIndex('student_subject_enrollment_status_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['status', 'enrolled_at', 'dropped_at']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['national_id']);
            $table->dropColumn('national_id');
        });
    }
};
