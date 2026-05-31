<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'client_administration_details',

            function (
                Blueprint $table
            ) {

                $table->id();

                $table->foreignId(
                    'client_id'
                )

                    ->constrained()

                    ->cascadeOnDelete();

                $table->string(
                    'administration_name'
                )

                    ->nullable();

                $table->string(
                    'department'
                )

                    ->nullable();

                $table->string(
                    'responsible_person'
                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'client_administration_details'
        );
    }
};