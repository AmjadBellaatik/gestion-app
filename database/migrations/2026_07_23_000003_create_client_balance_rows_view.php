<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Read-only SQL view unifying clients + resellers into one row shape for the
 * "Soldes clients" (Client Balances) accounting page, so resellers appear in
 * the SAME table/column as clients (no separate reseller column) — just
 * another row with a name and a balance, sourced from resellers.current_debt
 * instead of a live sales aggregate (resellers already track that themselves
 * via Reseller::recalculate()).
 *
 * `id` is a synthetic string key ("client-5" / "reseller-12") so rows from
 * both tables never collide; `source_type` + `source_id` let the app route
 * actions back to the real Client or Reseller record.
 *
 * OVERDUE_DAYS is inlined as 30 to match Client::OVERDUE_DAYS — a view can't
 * reference PHP constants; keep both in sync if that constant ever changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS client_balance_rows');

        DB::statement(<<<'SQL'
            CREATE VIEW client_balance_rows AS
            SELECT
                CONCAT('client-', c.id)                                        AS id,
                'client'                                                        AS source_type,
                c.id                                                            AS source_id,
                c.company_id                                                    AS company_id,
                c.client_type                                                   AS client_type,
                CASE c.client_type
                    WHEN 'company'        THEN c.company_name
                    WHEN 'administration' THEN c.administration_name
                    ELSE TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')))
                END                                                             AS display_name,
                c.phone                                                         AS phone,
                c.email                                                         AS email,
                c.ice                                                           AS ice,
                c.cin                                                           AS cin,
                c.representative_name                                           AS representative_name,
                c.is_active                                                     AS is_active,
                c.is_blocked                                                    AS is_blocked,
                c.created_at                                                    AS created_at,
                (SELECT MAX(s.sale_date) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL)           AS last_sale_at,
                (SELECT MAX(p.created_at) FROM payments p
                    WHERE p.client_id = c.id AND p.status = 'paid' AND p.deleted_at IS NULL) AS last_payment_at,
                (SELECT COALESCE(SUM(s.total), 0) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL)           AS total_sales_sum,
                (SELECT COALESCE(SUM(s.paid_amount), 0) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL)           AS total_payments_sum,
                (SELECT COALESCE(SUM(s.remaining_amount), 0) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL
                    AND s.payment_status IN ('unpaid', 'partial'))               AS outstanding_balance_sum,
                (SELECT COALESCE(SUM(GREATEST(s.paid_amount - s.total, 0)), 0) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL)           AS credit_balance_sum,
                (SELECT COUNT(*) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL
                    AND s.payment_status IN ('unpaid', 'partial'))               AS open_sales_count,
                (SELECT COUNT(*) FROM sales s
                    WHERE s.client_id = c.id AND s.deleted_at IS NULL
                    AND s.payment_status IN ('unpaid', 'partial')
                    AND s.sale_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY))      AS overdue_sales_count
            FROM clients c

            UNION ALL

            SELECT
                CONCAT('reseller-', r.id)                                      AS id,
                'reseller'                                                      AS source_type,
                r.id                                                            AS source_id,
                r.company_id                                                    AS company_id,
                'reseller'                                                      AS client_type,
                r.name                                                          AS display_name,
                r.phone                                                         AS phone,
                r.email                                                         AS email,
                r.ice                                                           AS ice,
                NULL                                                            AS cin,
                r.representative_name                                          AS representative_name,
                r.is_active                                                     AS is_active,
                r.is_blocked                                                    AS is_blocked,
                r.created_at                                                    AS created_at,
                (SELECT MAX(s.sale_date) FROM sales s
                    WHERE s.reseller_id = r.id AND s.deleted_at IS NULL)         AS last_sale_at,
                (SELECT MAX(p.created_at) FROM payments p
                    INNER JOIN sales s2 ON s2.id = p.sale_id
                    WHERE s2.reseller_id = r.id AND p.status = 'paid' AND p.deleted_at IS NULL) AS last_payment_at,
                (SELECT COALESCE(SUM(s.total), 0) FROM sales s
                    WHERE s.reseller_id = r.id AND s.deleted_at IS NULL)         AS total_sales_sum,
                r.total_paid                                                    AS total_payments_sum,
                r.current_debt                                                  AS outstanding_balance_sum,
                r.credit_balance                                                AS credit_balance_sum,
                (SELECT COUNT(*) FROM sales s
                    WHERE s.reseller_id = r.id AND s.deleted_at IS NULL
                    AND s.payment_status IN ('unpaid', 'partial'))               AS open_sales_count,
                (SELECT COUNT(*) FROM sales s
                    WHERE s.reseller_id = r.id AND s.deleted_at IS NULL
                    AND s.payment_status IN ('unpaid', 'partial')
                    AND s.sale_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY))      AS overdue_sales_count
            FROM resellers r
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS client_balance_rows');
    }
};
