<?php

use App\Services\EmployeeSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && Schema::hasTable('users')) {
            EmployeeSyncService::syncAllMissing();
        }
    }

    public function down(): void
    {
        // Data backfill is non-destructive and preserved on rollback.
    }
};
