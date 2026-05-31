<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_types', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('code')
                ->unique();

            $table->string('color')
                ->nullable();

            $table->boolean('affects_warranty')
                ->default(false);

            $table->boolean('billable')
                ->default(true);

            $table->boolean('active')
                ->default(true);

            $table->longText('description')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'repair_types'
        );
    }
};