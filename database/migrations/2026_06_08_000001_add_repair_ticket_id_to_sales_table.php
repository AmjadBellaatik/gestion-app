<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales', 'repair_ticket_id')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('repair_ticket_id')
                ->nullable()
                ->after('reseller_id')
                ->constrained('repair_tickets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'repair_ticket_id')) {
                $table->dropForeign(['repair_ticket_id']);
                $table->dropColumn('repair_ticket_id');
            }
        });
    }
};
