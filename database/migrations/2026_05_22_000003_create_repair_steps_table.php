<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_steps', function (Blueprint $table) {

            $table->id();

            $table->foreignId('repair_ticket_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->string('title');

            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'done'])
                ->default('pending');

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('performed_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_steps');
    }
};
