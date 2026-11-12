<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-Branch Management - Phase 2.
 * Adds a nullable, indexed branch_id to the remaining operational tables and
 * back-fills existing rows to the main branch (when one exists).
 * Additive only: no data is deleted and nothing is dropped on up().
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'inventory_items', 'suppliers', 'purchase_orders',
        'hostels', 'cafeteria_items',
        'transport_vehicles', 'transport_routes',
        'library_copies', 'payroll_runs', 'staff_attendance',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->index();
                });
            }
        }

        $mainId = Schema::hasTable('branches')
            ? DB::table('branches')->where('is_main', true)->value('id')
            : null;

        if ($mainId) {
            // Also back-fill the Phase 1 tables that were created without a value.
            foreach (array_merge($this->tables, ['students', 'classes', 'invoices', 'employees']) as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                    DB::table($tableName)->whereNull('branch_id')->update(['branch_id' => $mainId]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
