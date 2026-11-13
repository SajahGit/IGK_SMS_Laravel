<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('grade_scale_id')->nullable()->after('department_id')
                ->constrained('grade_scales')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('nationality', 80)->nullable()->after('gender');
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->string('nationality', 80)->nullable()->after('phone');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->char('currency', 3)->default('LKR')->after('amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->char('currency', 3)->default('LKR')->after('total');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->char('currency', 3)->default('LKR')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('fee_structures', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('guardians', fn (Blueprint $table) => $table->dropColumn('nationality'));
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('nationality'));
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_scale_id');
        });
    }
};
