<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (! Schema::hasColumn('document_types', 'category')) {
                $table->string('category')->default('commercial')->after('prefix');
            }

            if (! Schema::hasColumn('document_types', 'blade_view')) {
                $column = Schema::hasColumn('document_types', 'template') ? 'template' : 'prefix';
                $table->string('blade_view')->nullable()->after($column);
            }

            if (! Schema::hasColumn('document_types', 'default_language')) {
                $table->string('default_language')->default('fr')->after('language');
            }

            if (! Schema::hasColumn('document_types', 'automatic_variables')) {
                $table->json('automatic_variables')->nullable()->after('blade_view');
            }

            if (! Schema::hasColumn('document_types', 'header_enabled')) {
                $table->boolean('header_enabled')->default(true)->after('automatic_variables');
            }

            if (! Schema::hasColumn('document_types', 'footer_enabled')) {
                $table->boolean('footer_enabled')->default(true)->after('header_enabled');
            }

            if (! Schema::hasColumn('document_types', 'affects_stock')) {
                $table->boolean('affects_stock')->default(false)->after('footer_enabled');
            }

            if (! Schema::hasColumn('document_types', 'affects_accounting')) {
                $table->boolean('affects_accounting')->default(false)->after('affects_stock');
            }
        });

        Schema::table('document_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('document_templates', 'category')) {
                $table->string('category')->default('commercial')->after('name');
            }

            if (! Schema::hasColumn('document_templates', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('blade_view');
            }

            if (! Schema::hasColumn('document_templates', 'variables')) {
                $table->json('variables')->nullable()->after('version');
            }

            if (! Schema::hasColumn('document_templates', 'header_config')) {
                $table->json('header_config')->nullable()->after('variables');
            }

            if (! Schema::hasColumn('document_templates', 'footer_config')) {
                $table->json('footer_config')->nullable()->after('header_config');
            }
        });

        Schema::create('document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('blade_view');
            $table->json('variables')->nullable();
            $table->json('header_config')->nullable();
            $table->json('footer_config')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();

            $table->unique(['document_template_id', 'version'], 'doc_template_version_unique');
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('documents', 'reseller_id')) {
                $table->foreignId('reseller_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('documents', 'document_date')) {
                $table->date('document_date')->nullable()->after('document_number');
            }

            if (! Schema::hasColumn('documents', 'document_template_id')) {
                $table->foreignId('document_template_id')->nullable()->after('document_type_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('documents', 'template_version')) {
                $table->unsignedInteger('template_version')->default(1)->after('document_template_id');
            }

            if (! Schema::hasColumn('documents', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('documents', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('pdf_path');
            }

            if (! Schema::hasColumn('documents', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(20)->after('subtotal');
            }

            if (! Schema::hasColumn('documents', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            }

            if (! Schema::hasColumn('documents', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('documents', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('documents', 'metadata')) {
                $table->json('metadata')->nullable()->after('generated_at');
            }
        });

        Schema::table('document_items', function (Blueprint $table) {
            if (! Schema::hasColumn('document_items', 'item_type')) {
                $table->string('item_type')->default('product')->after('document_id');
            }

            if (! Schema::hasColumn('document_items', 'motorcycle_unit_id')) {
                $table->foreignId('motorcycle_unit_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('document_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(20)->after('unit_price');
            }

            if (! Schema::hasColumn('document_items', 'metadata')) {
                $table->json('metadata')->nullable()->after('unit_type');
            }
        });

        Schema::create('motorcycle_homologations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorcycle_model_id')->constrained()->cascadeOnDelete();
            $table->string('homologation_number')->nullable();
            $table->date('homologation_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country')->nullable();
            $table->json('technical_data')->nullable();
            $table->string('source_document_path')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->unsignedInteger('template_version')->default(1);
            $table->string('checksum')->nullable();
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_pdfs');
        Schema::dropIfExists('motorcycle_homologations');
        Schema::dropIfExists('document_template_versions');
    }
};
