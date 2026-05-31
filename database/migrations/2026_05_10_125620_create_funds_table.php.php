<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->string('name');

            $table->string('type')

                ->default('cash');

            $table->decimal(
                'balance',
                15,
                2
            )->default(0);

            $table->boolean('is_active')

                ->default(true);

            $table->text('notes')

                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'funds'
        );
    }
};