<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('type', 'service')
            ->update(['type' => 'consumable']);

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `products` MODIFY `type` ENUM('motorcycle','part','accessory','trotinette','velo_electrique','velo_normal','consumable') NOT NULL"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `products` MODIFY `type` ENUM('motorcycle','part','service','accessory','trotinette','velo_electrique','velo_normal','consumable') NOT NULL"
        );
    }
};
