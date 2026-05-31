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

            if (! Schema::hasColumn('repair_tickets', 'ticket_number')) {

                $table->string('ticket_number')
                    ->nullable()
                    ->unique();

            }

            if (! Schema::hasColumn('repair_tickets', 'technician_id')) {

                $table->foreignId('technician_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

            }

            if (! Schema::hasColumn('repair_tickets', 'opened_at')) {

                $table->timestamp('opened_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'diagnostic_at')) {

                $table->timestamp('diagnostic_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'assigned_at')) {

                $table->timestamp('assigned_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'finished_at')) {

                $table->timestamp('finished_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'paid_at')) {

                $table->timestamp('paid_at')
                    ->nullable();

            }

            if (! Schema::hasColumn('repair_tickets', 'payment_status')) {

                $table->string('payment_status')
                    ->default('unpaid');

            }

            if (! Schema::hasColumn('repair_tickets', 'priority')) {

                $table->string('priority')
                    ->default('normal');

            }

        });
    }

    public function down(): void
    {
        //
    }
};