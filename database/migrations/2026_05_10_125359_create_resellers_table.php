<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('phone')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->decimal('credit_balance', 12, 2)
                ->default(0);

            $table->decimal('total_orders', 12, 2)
                ->default(0);

            $table->decimal('total_paid', 12, 2)
                ->default(0);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};