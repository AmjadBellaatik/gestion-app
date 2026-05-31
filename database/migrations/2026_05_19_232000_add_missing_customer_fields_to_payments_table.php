<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'client_id')) {
                $table->foreignId('client_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'document_id')) {
                $table->foreignId('document_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')
                    ->nullable()
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'document_id')) {
                $table->dropConstrainedForeignId('document_id');
            }

            if (Schema::hasColumn('payments', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }

            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
