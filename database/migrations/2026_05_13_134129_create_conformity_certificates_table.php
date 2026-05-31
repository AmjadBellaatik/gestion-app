<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conformity_certificates', function (
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

            $table->foreignId('motorcycle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('document_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('certificate_number')
                ->unique();

            $table->string('homologation_reference');

            $table->string('vin_number');

            $table->string('engine_number')
                ->nullable();

            $table->date('issued_at');

            $table->boolean('is_locked')
                ->default(true);

            $table->boolean('is_validated')
                ->default(false);

            $table->timestamp('validated_at')
                ->nullable();

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->longText('official_wording');

            $table->string('hash')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'conformity_certificates'
        );
    }
};