<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reimbursements')) {
            return;
        }

        Schema::table('reimbursements', function (Blueprint $table) {
            if (! Schema::hasColumn('reimbursements', 'warranty_claim_id')) {
                $table->unsignedBigInteger('warranty_claim_id')->nullable()->after('repair_ticket_id');
            }

            if (! Schema::hasColumn('reimbursements', 'reference_number')) {
                $table->string('reference_number', 191)->nullable()->after('supplier_id');
            }

            if (! Schema::hasColumn('reimbursements', 'request_date')) {
                $table->date('request_date')->nullable()->after('reference_number');
            }

            if (! Schema::hasColumn('reimbursements', 'expected_payment_date')) {
                $table->date('expected_payment_date')->nullable()->after('request_date');
            }

            if (! Schema::hasColumn('reimbursements', 'paid_date')) {
                $table->date('paid_date')->nullable()->after('expected_payment_date');
            }

            if (! Schema::hasColumn('reimbursements', 'requested_amount')) {
                $table->decimal('requested_amount', 15, 2)->default(0)->after('paid_date');
            }

            if (! Schema::hasColumn('reimbursements', 'approved_amount')) {
                $table->decimal('approved_amount', 15, 2)->default(0)->after('requested_amount');
            }

            if (! Schema::hasColumn('reimbursements', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('approved_amount');
            }
        });

        if (
            Schema::hasColumn('reimbursements', 'reference_number')
            && ! $this->indexExists('reimbursements', 'reimbursements_reference_number_index')
        ) {
            Schema::table('reimbursements', function (Blueprint $table) {
                $table->index('reference_number', 'reimbursements_reference_number_index');
            });
        }

        if (
            Schema::hasTable('warranty_claims')
            && Schema::hasColumn('reimbursements', 'warranty_claim_id')
            && ! $this->foreignKeyExists('reimbursements', 'reimbursements_warranty_claim_id_foreign')
        ) {
            Schema::table('reimbursements', function (Blueprint $table) {
                $table->foreign('warranty_claim_id', 'reimbursements_warranty_claim_id_foreign')
                    ->references('id')
                    ->on('warranty_claims')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reimbursements')) {
            return;
        }

        if ($this->foreignKeyExists('reimbursements', 'reimbursements_warranty_claim_id_foreign')) {
            Schema::table('reimbursements', function (Blueprint $table) {
                $table->dropForeign('reimbursements_warranty_claim_id_foreign');
            });
        }

        if ($this->indexExists('reimbursements', 'reimbursements_reference_number_index')) {
            Schema::table('reimbursements', function (Blueprint $table) {
                $table->dropIndex('reimbursements_reference_number_index');
            });
        }

        $columns = [
            'warranty_claim_id',
            'reference_number',
            'request_date',
            'expected_payment_date',
            'paid_date',
            'requested_amount',
            'approved_amount',
            'paid_amount',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('reimbursements', $column)) {
                Schema::table('reimbursements', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
