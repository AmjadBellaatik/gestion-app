<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId(
                'document_type_id'
            )

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId('client_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('sale_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId(
                'repair_ticket_id'
            )

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->string(
                'document_number'
            );

            $table->string('language')

                ->default('fr');

            $table->string('status')

                ->default('draft');

            $table->decimal(
                'subtotal',
                15,
                2
            )->default(0);

            $table->decimal(
                'tax',
                15,
                2
            )->default(0);

            $table->decimal(
                'total',
                15,
                2
            )->default(0);

            $table->longText('notes')

                ->nullable();

            $table->foreignId(
                'generated_by'
            )

                ->nullable()

                ->constrained('users')

                ->nullOnDelete();

            $table->softDeletes();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        //
    }
};
