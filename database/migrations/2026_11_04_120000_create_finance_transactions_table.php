<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('category', 100);
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('transaction_date');
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'transaction_date']);
            $table->index(['category', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
