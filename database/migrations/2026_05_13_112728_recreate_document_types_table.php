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

            $table->string('code');

            $table->string('name');

            $table->string('prefix');

            $table->unsignedBigInteger(
                'next_number'
            )->default(1);

            $table->longText('template')

                ->nullable();

            $table->string('language')

                ->default('fr');

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