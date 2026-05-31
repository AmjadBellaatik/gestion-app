<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('accreditation_reference')
                ->nullable();

            $table->string('logo')
                ->nullable();

            $table->string('stamp')
                ->nullable();

            $table->string('signature')
                ->nullable();

            $table->text('footer')
                ->nullable();

            $table->string('primary_color')
                ->nullable();

            $table->string('secondary_color')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'brands'
        );
    }
};
