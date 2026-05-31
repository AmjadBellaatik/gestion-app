<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'client_person_details',

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
                    'cin'
                )

                    ->nullable();

                $table->date(
                    'birth_date'
                )

                    ->nullable();

                $table->enum(
                    'gender',

                    [

                        'male',

                        'female',

                    ]

                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'client_person_details'
        );
    }
};