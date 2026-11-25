<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_attendance') && ! Schema::hasColumn('staff_attendance', 'employee_id')) {
            Schema::table('staff_attendance', function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->after('teacher_id')->constrained('employees')->nullOnDelete();
                $table->index(['employee_id', 'attendance_date']);
            });
        }

        // Backfill existing staff_attendance records with their corresponding employee_id
        if (Schema::hasTable('staff_attendance') && Schema::hasTable('employees')) {
            $employees = DB::table('employees')->whereNotNull('teacher_id')->get(['id', 'teacher_id']);
            foreach ($employees as $employee) {
                DB::table('staff_attendance')
                    ->where('teacher_id', $employee->teacher_id)
                    ->whereNull('employee_id')
                    ->update(['employee_id' => $employee->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_attendance') && Schema::hasColumn('staff_attendance', 'employee_id')) {
            Schema::table('staff_attendance', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }
    }
};
