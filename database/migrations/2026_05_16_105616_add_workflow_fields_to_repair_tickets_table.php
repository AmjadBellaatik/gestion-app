<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_tickets', function (
            Blueprint $table
        ) {

            /*
            |--------------------------------------------------------------------------
            | WORKFLOW TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'repair_tickets',
                'diagnostic_started_at'
            )) {

                $table->timestamp(
                    'diagnostic_started_at'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'repair_tickets',
                'repair_started_at'
            )) {

                $table->timestamp(
                    'repair_started_at'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'repair_tickets',
                'completed_at'
            )) {

                $table->timestamp(
                    'completed_at'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'repair_tickets',
                'delivered_at'
            )) {

                $table->timestamp(
                    'delivered_at'
                )

                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | FINANCIALS
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'repair_tickets',
                'parts_total'
            )) {

                $table->decimal(
                    'parts_total',
                    15,
                    2
                )

                    ->default(0);
            }

            if (! Schema::hasColumn(
                'repair_tickets',
                'labor_total'
            )) {

                $table->decimal(
                    'labor_total',
                    15,
                    2
                )

                    ->default(0);
            }

            if (! Schema::hasColumn(
                'repair_tickets',
                'total'
            )) {

                $table->decimal(
                    'total',
                    15,
                    2
                )

                    ->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | WARRANTY
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'repair_tickets',
                'is_warranty'
            )) {

                $table->boolean(
                    'is_warranty'
                )

                    ->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (
            Blueprint $table
        ) {

            $table->dropColumn([

                'diagnostic_started_at',
                'repair_started_at',
                'completed_at',
                'delivered_at',

                'parts_total',
                'labor_total',
                'total',

                'is_warranty',

            ]);
        });
    }
};