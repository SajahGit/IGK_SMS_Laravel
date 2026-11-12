<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafeteria_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->unique()->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cafeteria_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->date('order_date')->index();
            $table->string('status', 20)->default('reserved')->index();
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'order_date', 'status']);
        });

        Schema::create('cafeteria_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('cafeteria_orders')->restrictOnDelete();
            $table->foreignId('cafeteria_item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->unique(['order_id', 'cafeteria_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafeteria_order_items');
        Schema::dropIfExists('cafeteria_orders');
        Schema::dropIfExists('cafeteria_items');
    }
};
