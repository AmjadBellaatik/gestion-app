# Migration Compatibility Report

Target: PlanetHoster shared hosting, PHP 8.4, MySQL 5.7+, MariaDB 10.x, utf8mb4.

Validation command:

```bash
php artisan migrate:fresh --force
```

Local validation result: passed using Laragon PHP 8.5 CLI against the configured MySQL database.

## Global Index-Length Compatibility

`app/Providers/AppServiceProvider.php` already contains:

```php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    Schema::defaultStringLength(191);
}
```

This keeps Laravel default `string()` columns at 191 characters during migrations, preventing utf8mb4 indexed string overflow on older MySQL/MariaDB configurations with strict key-length limits.

## Migration Audit Summary

- Laravel default `users`, `password_reset_tokens`, `sessions`, `cache`, and `jobs` tables were reviewed.
- Spatie Permission tables were reviewed. Their `name + guard_name` unique keys and `morphs('model')` columns are protected by the 191 default string length.
- ERP custom migrations were reviewed for `unique()`, `index()`, `primary()`, `foreignId()`, `morphs()`, and composite indexes.
- No explicit indexed `string(255)` columns were found that need manual length reduction after the global 191 default.
- Migration order conflicts and duplicate table/column creation issues were fixed.

## Modified Files

| File | Reason | Risk | Compatibility improvement |
| --- | --- | --- | --- |
| `2026_05_08_154334_create_payments_table.php` | Removed early `sale_id` and `client_id` foreign keys before referenced tables exist. Later migrations add them safely. | Low | Clean installs no longer fail before `sales` exists. |
| `2026_05_10_125550_create_repair_tickets_table.php` | Removed early `technician_id` foreign key before `technicians` exists. Later migration adds it. | Low | Clean installs no longer fail before `technicians` exists. |
| `2026_05_10_125610_create_warranty_claims_table.php.php` | Converted mistaken migration that created `warranties` into no-op. A later migration creates the correct warranty tables. | Medium | Removes duplicate/conflicting `warranties` table creation. |
| `2026_05_13_112728_recreate_document_types_table.php` | Guarded recreate migration with `Schema::hasTable()` and made down no-op. | Low | Prevents duplicate `document_types` creation. |
| `2026_05_13_113529_recreate_documents_table.php` | Guarded recreate migration with `Schema::hasTable()` and made down no-op. | Low | Prevents duplicate `documents` creation. |
| `2026_05_17_000001_add_deleted_at_to_payments_table.php` | Added `Schema::hasColumn()` checks. | Low | Prevents duplicate `deleted_at` column creation. |
| `2026_05_17_000002_add_company_id_to_payments_table.php` | Added `Schema::hasColumn()` checks. | Low | Prevents duplicate `company_id` column creation. |
| `2026_05_17_005627_remove_old_motorcycles_table.php` | Kept legacy `motorcycles` table instead of dropping it while historical FKs may still reference it. | Medium | Avoids FK drop failure on strict MySQL/MariaDB. |
| `2026_05_18_160000_make_client_person_fields_nullable.php` | Added per-column existence checks before `change()`. | Low | Clean installs no longer fail when legacy columns are absent. |

## Remaining Notes

- `Schema::defaultStringLength(191)` is the key fix for the original `Specified key was too long; max key length is 1000 bytes` class of errors.
- The successful `migrate:fresh --force` run also validates there are no remaining duplicate table creation failures in the current chain.
- Shared hosting should use PHP 8.4+ and the same `.env.production` database charset/collation defaults Laravel expects: `utf8mb4` / `utf8mb4_unicode_ci`.
