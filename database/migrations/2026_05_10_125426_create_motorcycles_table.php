<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motorcycles', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('reseller_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('brand');

            $table->string('model');

            $table->year('year')
                ->nullable();

            $table->string('color')
                ->nullable();

            $table->string('vin_number')
                ->unique();

            $table->string('engine_number')
                ->nullable();

            $table->enum('status', [

                'available',
                'reserved',
                'sold',
                'in_repair',

            ])->default('available');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'motorcycles'
        );
    }
};