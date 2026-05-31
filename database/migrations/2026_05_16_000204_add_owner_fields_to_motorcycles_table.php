<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn(
                'motorcycles',
                'sold_at'
            )) {

                $table->date(
                    'sold_at'
                )

                    ->nullable();
            }

            if (! Schema::hasColumn(
                'motorcycles',
                'is_sold'
            )) {

                $table->boolean(
                    'is_sold'
                )

                    ->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (
            Blueprint $table
        ) {

            if (Schema::hasColumn(
                'motorcycles',
                'sold_at'
            )) {

                $table->dropColumn(
                    'sold_at'
                );
            }

            if (Schema::hasColumn(
                'motorcycles',
                'is_sold'
            )) {

                $table->dropColumn(
                    'is_sold'
                );
            }
        });
    }
};