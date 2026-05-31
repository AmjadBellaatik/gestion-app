<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_ticket_technicians', function (Blueprint $table) {

            $table->id();

            $table->foreignId('repair_ticket_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('permission', ['view', 'modify'])
                ->default('modify');

            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['repair_ticket_id', 'technician_id']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_ticket_technicians');
    }
};
