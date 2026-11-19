<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('status', 20)->default('upcoming');
        });
        Schema::table('terms', function (Blueprint $table) {
            $table->string('status', 20)->default('upcoming');
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->string('status', 20)->default('scheduled');
        });

        DB::table('academic_years')->where('is_current', true)->update(['status' => 'active']);
        DB::table('terms')->where('is_current', true)->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
