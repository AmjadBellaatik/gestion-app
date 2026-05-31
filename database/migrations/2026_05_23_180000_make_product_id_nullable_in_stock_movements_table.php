<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK only if it exists (compatible with older MySQL versions)
        $this->dropForeignKeyIfExists('stock_movements', 'stock_movements_product_id_foreign');
        DB::statement('ALTER TABLE `stock_movements` MODIFY `product_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `stock_movements` ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('stock_movements', 'stock_movements_product_id_foreign');
        DB::statement('ALTER TABLE `stock_movements` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `stock_movements` ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE');
    }

    private function dropForeignKeyIfExists(string $table, string $fkName): void
    {
        $dbName   = DB::getDatabaseName();
        $exists   = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $fkName)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
        }
    }
};
