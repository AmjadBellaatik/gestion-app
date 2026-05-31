<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn(
                'technicians',
                'completed_repairs'
            )) {

                $table->integer(
                    'completed_repairs'
                )

                    ->default(0);
            }

            if (! Schema::hasColumn(
                'technicians',
                'generated_revenue'
            )) {

                $table->decimal(
                    'generated_revenue',
                    15,
                    2
                )

                    ->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'completed_repairs',
                'generated_revenue',

            ]);
        });
    }
};