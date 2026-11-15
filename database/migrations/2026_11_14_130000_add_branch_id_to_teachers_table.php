<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers') && ! Schema::hasColumn('teachers', 'branch_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('department_id')->constrained('branches')->nullOnDelete();
            });

            // Database-agnostic backfill from linked user records
            $teachers = DB::table('teachers')->select('id', 'user_id')->get();
            foreach ($teachers as $t) {
                $userBranchId = DB::table('users')->where('id', $t->user_id)->value('branch_id');
                if ($userBranchId) {
                    DB::table('teachers')->where('id', $t->id)->update(['branch_id' => $userBranchId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'branch_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
