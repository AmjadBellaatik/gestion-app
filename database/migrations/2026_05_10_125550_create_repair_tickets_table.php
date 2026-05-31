<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_tickets', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId('client_id')

                ->nullable()

                ->constrained()

                ->nullOnDelete();

            $table->foreignId('motorcycle_id')

                ->nullable()

                ->constrained('products')

                ->nullOnDelete();

            $table->string(
                'ticket_number'
            );

            $table->enum(

                'repair_type',

                [

                    'warranty',
                    'paid',
                    'internal',
                    'reimbursement',

                ]

            )->default('paid');

            $table->string('status')

                ->default('open');

            $table->longText(
                'problem_description'
            )

                ->nullable();

            $table->longText(
                'diagnostic'
            )

                ->nullable();

            $table->decimal(
                'labor_cost',
                15,
                2
            )->default(0);

            $table->decimal(
                'parts_cost',
                15,
                2
            )->default(0);

            $table->decimal(
                'total_cost',
                15,
                2
            )->default(0);

            $table->boolean(
                'is_warranty'
            )->default(false);

            $table->foreignId(
                'created_by'
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
        Schema::dropIfExists(
            'repair_tickets'
        );
    }
};
