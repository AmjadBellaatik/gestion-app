<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('document_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('year');

            $table->string('prefix');

            $table->integer('current_number')
                ->default(0);

            $table->integer('padding')
                ->default(4);

            $table->boolean('yearly_reset')
                ->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'document_sequences'
        );
    }
};