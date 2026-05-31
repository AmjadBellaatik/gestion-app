<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_items');

        DB::statement(<<<'SQL'
            CREATE VIEW stock_items AS
            SELECT
                CONCAT('product-', products.id) AS id,
                products.company_id AS company_id,
                'product' AS item_kind,
                products.id AS product_id,
                NULL AS motorcycle_model_id,
                products.name AS name,
                products.sku AS reference,
                products.type AS type,
                products.stock_alert AS stock_alert,
                products.created_at AS created_at,
                products.updated_at AS updated_at
            FROM products
            WHERE products.deleted_at IS NULL
            UNION ALL
            SELECT
                CONCAT('motorcycle-model-', motorcycle_models.id) AS id,
                NULL AS company_id,
                'motorcycle_model' AS item_kind,
                NULL AS product_id,
                motorcycle_models.id AS motorcycle_model_id,
                TRIM(CONCAT(COALESCE(motorcycle_models.marque, ''), ' ', COALESCE(motorcycle_models.modele, ''))) AS name,
                motorcycle_models.titre_homologation AS reference,
                motorcycle_models.type AS type,
                motorcycle_models.stock_alert AS stock_alert,
                motorcycle_models.created_at AS created_at,
                motorcycle_models.updated_at AS updated_at
            FROM motorcycle_models
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_items');
    }
};
