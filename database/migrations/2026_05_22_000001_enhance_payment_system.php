<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add blocking fields to clients
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('clients', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable()->after('is_blocked');
            }
        });

        // 2. Update payments table
        Schema::table('payments', function (Blueprint $table) {
            // Make sale_id nullable (payments can now belong to repair tickets too)
            $table->foreignId('sale_id')->nullable()->change();

            // Add repair_ticket_id
            if (! Schema::hasColumn('payments', 'repair_ticket_id')) {
                $table->foreignId('repair_ticket_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            // Drop redundant method column (keep payment_method)
            if (Schema::hasColumn('payments', 'method')) {
                $table->dropColumn('method');
            }
        });

        // 3. Add status to bank_transfer_payments
        Schema::table('bank_transfer_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_transfer_payments', 'status')) {
                $table->string('status')->default('sent')->after('confirmation_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'blocked_reason']);
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'repair_ticket_id')) {
                $table->dropConstrainedForeignId('repair_ticket_id');
            }
            $table->string('method')->default('cash')->after('amount');
        });

        Schema::table('bank_transfer_payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
