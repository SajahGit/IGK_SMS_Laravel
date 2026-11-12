<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique('inventory_items_sku_unique');
            $table->unique(['branch_id', 'sku'], 'inventory_items_branch_sku_unique');
        });
    }

    public function down(): void
    {
        $hasDuplicateSkus = DB::table('inventory_items')
            ->select('sku')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateSkus) {
            throw new RuntimeException('Cannot restore global SKU uniqueness while different branches share a SKU.');
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique('inventory_items_branch_sku_unique');
            $table->unique('sku', 'inventory_items_sku_unique');
        });
    }
};
