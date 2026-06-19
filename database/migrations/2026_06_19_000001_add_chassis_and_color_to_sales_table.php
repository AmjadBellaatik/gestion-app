<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chassis number + color fields for electric scooters / bicycles.
 *   - chassis_number (Numéro de châssis)
 *   - color          (Couleur)
 * Both nullable — only populated for trotinette / velo_electrique / velo_normal sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'chassis_number')) {
                $table->string('chassis_number')->nullable()->after('purchase_order_number');
            }
            if (! Schema::hasColumn('sales', 'color')) {
                $table->string('color')->nullable()->after('chassis_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            foreach (['chassis_number', 'color'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
