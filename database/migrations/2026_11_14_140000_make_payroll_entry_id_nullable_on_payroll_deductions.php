<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_deductions')) {
            Schema::table('payroll_deductions', function (Blueprint $table) {
                if (! Schema::hasColumn('payroll_deductions', 'payroll_month')) {
                    $table->string('payroll_month', 7)->nullable()->after('employee_id'); // e.g. 2026-11
                }
                if (! Schema::hasColumn('payroll_deductions', 'effective_date')) {
                    $table->date('effective_date')->nullable()->after('payroll_month');
                }
                if (! Schema::hasColumn('payroll_deductions', 'status')) {
                    $table->string('status', 30)->default('pending')->after('notes'); // pending, applied, cancelled
                }
            });

            // Make payroll_entry_id nullable so Super Admin can record deductions prior to payroll batch generation
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    Schema::table('payroll_deductions', function (Blueprint $table) {
                        $table->unsignedBigInteger('payroll_entry_id')->nullable()->change();
                    });
                } catch (\Throwable $e) {
                    // Handled gracefully
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_deductions')) {
            Schema::table('payroll_deductions', function (Blueprint $table) {
                $table->dropColumn(['payroll_month', 'effective_date', 'status']);
            });
        }
    }
};
