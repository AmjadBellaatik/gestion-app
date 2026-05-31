<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (
            Blueprint $table
        ) {

            if (! Schema::hasColumn('brands', 'logo_dark')) {

                $table->string('logo_dark')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'logo_light')) {

                $table->string('logo_light')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'pdf_header')) {

                $table->string('pdf_header')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'pdf_footer')) {

                $table->string('pdf_footer')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'stamp_image')) {

                $table->string('stamp_image')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'director_signature')) {

                $table->string('director_signature')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'legal_notice')) {

                $table->longText('legal_notice')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'invoice_terms')) {

                $table->longText('invoice_terms')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'warranty_terms')) {

                $table->longText('warranty_terms')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'color_palette')) {

                $table->json('color_palette')
                    ->nullable();

            }

            if (! Schema::hasColumn('brands', 'qr_code')) {

                $table->string('qr_code')
                    ->nullable();

            }

        });
    }

    public function down(): void
    {
        //
    }
};