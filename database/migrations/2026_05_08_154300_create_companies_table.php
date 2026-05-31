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
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('legal_name')->nullable();

            // Moroccan Legal Information
            $table->string('ice')->nullable();
            $table->string('rc')->nullable();
            $table->string('if')->nullable();
            $table->string('patente')->nullable();

            // Contact Information
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Branding
            $table->string('logo')->nullable();
            $table->string('stamp')->nullable();
            $table->string('signature')->nullable();

            // Settings
            $table->string('currency', 10)->default('MAD');
            $table->string('default_language', 5)->default('fr');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};