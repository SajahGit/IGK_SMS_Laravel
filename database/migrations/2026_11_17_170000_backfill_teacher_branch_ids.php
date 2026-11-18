<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers') && Schema::hasTable('users')) {
            $teachers = DB::table('teachers')->whereNull('branch_id')->select('id', 'user_id')->get();
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
        // No destructive reverse needed for data synchronization
    }
};
