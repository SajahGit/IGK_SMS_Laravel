<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts module completion (SOP §11 / M19-M21). Purely additive:
 *  - number_sequences : lockable counters so invoice / receipt numbers are gapless and race-free
 *  - invoice_items    : billed lines (SOP: immutable after issue; negative amount = discount line)
 *  - payment_reversals: the "offsetting record" of PR-5 / TC-FIN-18 (original payment is retained)
 *  - invoices         : subtotal, discount, cancelled_at, cancellation_reason
 * Existing rows are back-filled so school:verify-integrity stays green after migrating.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('number_sequences')) {
            Schema::create('number_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('name', 60)->unique();
                $table->unsignedBigInteger('last_value')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('invoices', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'cancellation_reason')) {
                $table->string('cancellation_reason', 255)->nullable();
            }
        });

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();
                $table->string('description', 150);
                $table->decimal('amount', 12, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_reversals')) {
            Schema::create('payment_reversals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('reason', 255);
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at');
                $table->timestamps();
            });
        }

        // Back-fill: subtotal = total, one migrated line per existing invoice.
        DB::table('invoices')->where('subtotal', 0)->update(['subtotal' => DB::raw('total')]);

        DB::table('invoices')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('invoice_items')->whereColumn('invoice_items.invoice_id', 'invoices.id');
            })
            ->chunkById(500, function ($invoices) {
                $rows = [];
                foreach ($invoices as $invoice) {
                    $rows[] = [
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => null,
                        'description' => 'Fees billed (migrated)',
                        'amount' => $invoice->total,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('invoice_items')->insert($rows);
            });

        // Back-fill: reversals made before this migration get their offsetting record.
        DB::table('payments')
            ->whereNotNull('reversed_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('payment_reversals')->whereColumn('payment_reversals.payment_id', 'payments.id');
            })
            ->chunkById(500, function ($payments) {
                $rows = [];
                foreach ($payments as $payment) {
                    $rows[] = [
                        'payment_id' => $payment->id,
                        'invoice_id' => $payment->invoice_id,
                        'amount' => $payment->amount,
                        'reason' => $payment->reversal_reason ?: 'Reversal recorded before offsetting records existed',
                        'reversed_by' => null,
                        'reversed_at' => $payment->reversed_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('payment_reversals')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
        Schema::dropIfExists('invoice_items');

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['subtotal', 'discount', 'cancelled_at', 'cancellation_reason'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('number_sequences');
    }
};
