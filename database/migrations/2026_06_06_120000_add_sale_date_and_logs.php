<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MANUAL SALE DATE MANAGEMENT
 * ---------------------------
 * 1. Adds sales.sale_date — the effective business date of a sale, distinct
 *    from created_at (the immutable DB creation timestamp). Both coexist.
 *    Existing rows are backfilled from DATE(created_at) so history is preserved.
 * 2. Creates sale_date_logs — an immutable audit trail of every sale_date
 *    change (who, old, new, when).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'sale_date')) {
                // nullable at DB level for safe backfill; the model guarantees a value.
                $table->date('sale_date')->nullable()->after('sale_number');
                $table->index('sale_date');
            }
        });

        // Backfill: effective date = the date the row was created.
        DB::statement('UPDATE sales SET sale_date = DATE(created_at) WHERE sale_date IS NULL');

        if (! Schema::hasTable('sale_date_logs')) {
            Schema::create('sale_date_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();   // snapshot, survives user deletion
                $table->date('old_date')->nullable();
                $table->date('new_date');
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->index(['company_id', 'sale_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_date_logs');

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'sale_date')) {
                $table->dropIndex(['sale_date']);
                $table->dropColumn('sale_date');
            }
        });
    }
};
