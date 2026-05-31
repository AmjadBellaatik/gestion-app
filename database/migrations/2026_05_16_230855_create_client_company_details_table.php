<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'client_company_details',

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
                    'legal_name'
                )

                    ->nullable();

                $table->string(
                    'ice'
                )

                    ->nullable();

                $table->string(
                    'rc'
                )

                    ->nullable();

                $table->string(
                    'if'
                )

                    ->nullable();

                $table->string(
                    'patente'
                )

                    ->nullable();

                $table->string(
                    'representative_name'
                )

                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'client_company_details'
        );
    }
};