<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'sale_id')) {
                $table->foreignId('sale_id')
                    ->nullable()
                    ->after('reseller_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('documents', 'repair_ticket_id')) {
                $table->foreignId('repair_ticket_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('documents', 'generated_by')) {
                $table->foreignId('generated_by')
                    ->nullable()
                    ->after('generated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'generated_by')) {
                $table->dropConstrainedForeignId('generated_by');
            }

            if (Schema::hasColumn('documents', 'repair_ticket_id')) {
                $table->dropConstrainedForeignId('repair_ticket_id');
            }

            if (Schema::hasColumn('documents', 'sale_id')) {
                $table->dropConstrainedForeignId('sale_id');
            }
        });
    }
};
