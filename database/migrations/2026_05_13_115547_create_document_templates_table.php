<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('document_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('blade_view');

            $table->string('language')
                ->default('fr');

            $table->boolean('is_default')
                ->default(false);

            $table->string('orientation')
                ->default('portrait');

            $table->string('paper_size')
                ->default('A4');

            $table->boolean('rtl')
                ->default(false);

            $table->boolean('footer_enabled')
                ->default(true);

            $table->boolean('header_enabled')
                ->default(true);

            $table->string('watermark')
                ->nullable();

            $table->boolean('signature_enabled')
                ->default(true);

            $table->boolean('stamp_enabled')
                ->default(true);

            $table->string('template_type')
                ->default('commercial');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'document_templates'
        );
    }
};