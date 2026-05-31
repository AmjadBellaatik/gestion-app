<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {

            $table->foreignId('sale_id')
                ->nullable()
                ->after('client_id')
                ->constrained('sales')
                ->nullOnDelete();

            $table->boolean('is_foreign_vehicle')
                ->default(false)
                ->after('motorcycle_unit_id');

            $table->string('foreign_brand')->nullable()->after('is_foreign_vehicle');
            $table->string('foreign_model')->nullable()->after('foreign_brand');
            $table->string('foreign_chassis')->nullable()->after('foreign_model');
            $table->unsignedSmallInteger('foreign_year')->nullable()->after('foreign_chassis');
            $table->string('foreign_color')->nullable()->after('foreign_year');
            $table->unsignedInteger('foreign_mileage')->default(0)->after('foreign_color');

        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn([
                'sale_id',
                'is_foreign_vehicle',
                'foreign_brand',
                'foreign_model',
                'foreign_chassis',
                'foreign_year',
                'foreign_color',
                'foreign_mileage',
            ]);
        });
    }
};
