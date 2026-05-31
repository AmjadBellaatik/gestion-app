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

            if (! Schema::hasColumn('repair_tickets', 'repair_type_id')) {

                $table->foreignId('repair_type_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

            }

        });
    }

    public function down(): void
    {
        //
    }
};