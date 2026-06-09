<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {

            // closed_at was added in 2026_06_09_000001 — only add if missing
            if (! Schema::hasColumn('repair_tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('delivered_at');
            }

            if (! Schema::hasColumn('repair_tickets', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            }

            if (! Schema::hasColumn('repair_tickets', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('total_cost');
            }

            if (! Schema::hasColumn('repair_tickets', 'remaining_amount')) {
                $table->decimal('remaining_amount', 10, 2)->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {

            if (Schema::hasColumn('repair_tickets', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('repair_tickets', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }

            if (Schema::hasColumn('repair_tickets', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }

            // Do NOT drop closed_at here — it is owned by migration 000001
        });
    }
};
