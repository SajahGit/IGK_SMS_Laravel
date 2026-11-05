<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('date');
            $table->unique(
                ['class_id', 'class_subject_id', 'date', 'start_time'],
                'attendance_sessions_class_subject_date_time_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropUnique('attendance_sessions_class_subject_date_time_unique');
            $table->dropColumn('start_time');
        });
    }
};
