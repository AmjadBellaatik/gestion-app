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

            if (! Schema::hasColumn('repair_tickets', 'mileage')) {

                $table->integer('mileage')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'diagnosis')) {

                $table->longText('diagnosis')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'technician_notes')) {

                $table->longText('technician_notes')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'before_state')) {

                $table->longText('before_state')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'after_state')) {

                $table->longText('after_state')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'warranty_status')) {

                $table->string('warranty_status')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'started_at')) {

                $table->timestamp('started_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'completed_at')) {

                $table->timestamp('completed_at')
                    ->nullable();

            }

        });
    }

    public function down(): void
    {
        //
    }
};