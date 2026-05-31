<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (
            Blueprint $table
        ) {

            $table->decimal(
                'credit_limit',
                15,
                2
            )->default(0);

            $table->decimal(
                'current_debt',
                15,
                2
            )->default(0);

            $table->string(
                'payment_terms'
            )->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'credit_limit',
                'current_debt',
                'payment_terms',

            ]);

        });
    }
};