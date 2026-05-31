<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('code')
                ->unique();

            $table->string('prefix')
                ->nullable();

            $table->boolean('affects_stock')
                ->default(false);

            $table->boolean('affects_accounting')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'document_types'
        );
    }
};