<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'transaction_date')) {
                $table->timestamp('transaction_date')->nullable()->after('description');
            }

            if (! Schema::hasColumn('transactions', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('transaction_date')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('transactions', 'transaction_date') ? 'transaction_date' : null,
                Schema::hasColumn('transactions', 'user_id') ? 'user_id' : null,
            ]));
        });
    }
};
