<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('apit_mode', 20)->default('primary')->after('base_salary');
        });

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('employee_epf', 12, 2)->default(0)->after('deduction');
            $table->decimal('employer_epf', 12, 2)->default(0)->after('employee_epf');
            $table->decimal('employer_etf', 12, 2)->default(0)->after('employer_epf');
            $table->decimal('apit_deduction', 12, 2)->default(0)->after('employer_etf');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn(['employee_epf', 'employer_epf', 'employer_etf', 'apit_deduction']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('apit_mode');
        });
    }
};
