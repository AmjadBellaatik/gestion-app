<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (
            Blueprint $table
        ) {

            $table->foreignId(
                'company_id'
            )

                ->nullable()

                ->constrained()

                ->cascadeOnDelete();

            $table->string(
                'title'
            )->nullable();

            $table->text(
                'description'
            )->nullable();

            $table->decimal(
                'amount',
                15,
                2
            )->default(0);

            $table->date(
                'expense_date'
            )->nullable();

            $table->string(
                'category'
            )->nullable();

            $table->string(
                'status'
            )->default('pending');

        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'company_id',
                'title',
                'description',
                'amount',
                'expense_date',
                'category',
                'status',

            ]);

        });
    }
};