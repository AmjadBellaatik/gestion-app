<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {

            $table->decimal('discount_amount', 15, 2)
                ->default(0)
                ->after('total_cost');

            $table->boolean('discount_validated')
                ->default(false)
                ->after('discount_amount');

            $table->foreignId('discount_validated_by')
                ->nullable()
                ->after('discount_validated')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('discount_validated_at')
                ->nullable()
                ->after('discount_validated_by');

            $table->text('discount_note')
                ->nullable()
                ->after('discount_validated_at');

            $table->string('report_path')
                ->nullable()
                ->after('discount_note');

            $table->unsignedBigInteger('invoice_document_id')
                ->nullable()
                ->after('report_path');

        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {
            $table->dropForeign(['discount_validated_by']);
            $table->dropColumn([
                'discount_amount',
                'discount_validated',
                'discount_validated_by',
                'discount_validated_at',
                'discount_note',
                'report_path',
                'invoice_document_id',
            ]);
        });
    }
};
