<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_transfers')) {
            Schema::create('branch_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('person_id');
                $table->string('person_category', 30); // student, employee, teacher
                $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('previous_admission_no', 60)->nullable();
                $table->string('new_admission_no', 60)->nullable();
                $table->date('transfer_date');
                $table->text('reason')->nullable();
                $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['person_category', 'person_id']);
                $table->index(['from_branch_id', 'to_branch_id']);
            });
        }

        Schema::table('payroll_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_entries', 'unpaid_leave_days')) {
                $table->unsignedSmallInteger('unpaid_leave_days')->default(0)->after('deduction');
            }
            if (! Schema::hasColumn('payroll_entries', 'leave_deduction')) {
                $table->decimal('leave_deduction', 12, 2)->default(0)->after('unpaid_leave_days');
            }
            if (! Schema::hasColumn('payroll_entries', 'manual_deduction')) {
                $table->decimal('manual_deduction', 12, 2)->default(0)->after('leave_deduction');
            }
            if (! Schema::hasColumn('payroll_entries', 'manual_deduction_reason')) {
                $table->string('manual_deduction_reason', 255)->nullable()->after('manual_deduction');
            }
        });

        if (! Schema::hasTable('payroll_deductions')) {
            Schema::create('payroll_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('payroll_month', 7)->nullable();
                $table->date('effective_date')->nullable();
                $table->string('type', 40)->default('manual'); // leave_unpaid, manual, advance, penalty, other
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedSmallInteger('days')->default(0);
                $table->string('reason', 255)->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('pending');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['payroll_entry_id']);
                $table->index(['employee_id']);
                $table->index(['payroll_month']);
                $table->index(['status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn(['unpaid_leave_days', 'leave_deduction', 'manual_deduction', 'manual_deduction_reason']);
        });

        Schema::dropIfExists('branch_transfers');
    }
};
