<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SOP M19: a fee structure is a price list made of fee items (name, amount >= 0, mandatory flag).
 * SOP M20: an invoice line can point at the fee item it was billed from.
 *
 * Additive and safe on existing data: structures without items keep billing their own amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_items')) {
            Schema::create('fee_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
                $table->string('name', 100);
                $table->decimal('amount', 12, 2);
                $table->boolean('is_mandatory')->default(true);
                $table->timestamps();
                $table->unique(['fee_structure_id', 'name']);
            });
        }

        if (! Schema::hasColumn('invoice_items', 'fee_item_id')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('fee_item_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_items', 'fee_item_id')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropIndex(['fee_item_id']);
                $table->dropColumn('fee_item_id');
            });
        }

        Schema::dropIfExists('fee_items');
    }
};
