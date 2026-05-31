<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->legacyMotorcycleReferences() as $table) {
            $this->dropColumnSafely($table, 'motorcycle_id');
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy Motorcycles Table
        |--------------------------------------------------------------------------
        |
        | Do not drop the old motorcycles table in this historical cleanup.
        | Some installations may still have foreign keys pointing to it from
        | migrations that ran before the motorcycle_units refactor. Keeping the
        | table makes this migration safe on MySQL 5.7, MariaDB 10.x, and shared
        | hosting where FK names/state can differ from a local development DB.
        |
        */
    }

    public function down(): void
    {
        //
    }

    private function legacyMotorcycleReferences(): array
    {
        return [
            'stock_movements',
            'warranty_contracts',
            'repair_tickets',
            'documents',
            'document_items',
            'conformity_certificates',
            'sale_items',
        ];
    }

    private function dropColumnSafely(string $tableName, string $columnName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        foreach ($this->foreignKeysForColumn($tableName, $columnName) as $foreignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        }

        if (! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }

    private function foreignKeysForColumn(string $tableName, string $columnName): array
    {
        $database = DB::getDatabaseName();

        return collect(DB::select(
            <<<'SQL'
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL,
            [$database, $tableName, $columnName]
        ))
            ->pluck('CONSTRAINT_NAME')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
};
