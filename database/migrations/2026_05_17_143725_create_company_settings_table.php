<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'company_settings',

            function (
                Blueprint $table
            ) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Company
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'company_id'
                )

                    ->unique()

                    ->constrained()

                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Branding
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'logo'
                )

                    ->nullable();

                $table->string(
                    'stamp'
                )

                    ->nullable();

                $table->string(
                    'signature'
                )

                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Footer
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'footer'
                )

                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Numbering
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'invoice_prefix'
                )

                    ->default('INV');

                $table->string(
                    'quotation_prefix'
                )

                    ->default('DEV');

                $table->string(
                    'repair_prefix'
                )

                    ->default('REP');

                /*
                |--------------------------------------------------------------------------
                | Language
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'default_language'
                )

                    ->default('fr');

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'company_settings'
        );
    }
};