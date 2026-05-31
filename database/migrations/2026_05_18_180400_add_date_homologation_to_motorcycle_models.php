<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            if (! Schema::hasColumn('motorcycle_models', 'date_homologation')) {
                $table->date('date_homologation')
                    ->nullable()
                    ->after('titre_homologation');
            }
        });

        if (Schema::hasTable('motorcycle_homologations')) {
            \Illuminate\Support\Facades\DB::statement("
                update motorcycle_models mm
                inner join motorcycle_homologations mh on mh.motorcycle_model_id = mm.id
                set mm.date_homologation = mh.homologation_date
                where mm.date_homologation is null
                    and mh.homologation_date is not null
            ");
        }
    }

    public function down(): void
    {
        Schema::table('motorcycle_models', function (Blueprint $table) {
            if (Schema::hasColumn('motorcycle_models', 'date_homologation')) {
                $table->dropColumn('date_homologation');
            }
        });
    }
};
