<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate existing 'reimbursement' records to 'paid' before shrinking ENUM
        DB::table('repair_tickets')
            ->where('repair_type', 'reimbursement')
            ->update(['repair_type' => 'paid']);

        // 2. Remove 'reimbursement' from the repair_type ENUM
        DB::statement(
            "ALTER TABLE repair_tickets MODIFY COLUMN repair_type
             ENUM('warranty','paid','internal') NOT NULL DEFAULT 'paid'"
        );

        // 3. Consolidate mileage: copy foreign_mileage → mileage then drop the duplicate
        if (Schema::hasColumn('repair_tickets', 'foreign_mileage')) {
            DB::statement(
                "UPDATE repair_tickets
                 SET mileage = foreign_mileage
                 WHERE is_foreign_vehicle = 1
                   AND (mileage IS NULL OR mileage = 0)
                   AND foreign_mileage IS NOT NULL
                   AND foreign_mileage > 0"
            );

            Schema::table('repair_tickets', function (Blueprint $table) {
                $table->dropColumn('foreign_mileage');
            });
        }

        // 4. Add closed_at timestamp for the new 'closed' workflow status
        if (! Schema::hasColumn('repair_tickets', 'closed_at')) {
            Schema::table('repair_tickets', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('delivered_at');
            });
        }
    }

    public function down(): void
    {
        // Restore foreign_mileage column
        if (! Schema::hasColumn('repair_tickets', 'foreign_mileage')) {
            Schema::table('repair_tickets', function (Blueprint $table) {
                $table->unsignedInteger('foreign_mileage')->default(0)->after('foreign_color');
            });
        }

        // Restore reimbursement in ENUM
        DB::statement(
            "ALTER TABLE repair_tickets MODIFY COLUMN repair_type
             ENUM('warranty','paid','internal','reimbursement') NOT NULL DEFAULT 'paid'"
        );

        // Drop closed_at
        if (Schema::hasColumn('repair_tickets', 'closed_at')) {
            Schema::table('repair_tickets', function (Blueprint $table) {
                $table->dropColumn('closed_at');
            });
        }
    }
};
