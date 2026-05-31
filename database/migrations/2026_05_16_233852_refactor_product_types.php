<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Convert old motorcycle products
        |--------------------------------------------------------------------------
        */

        DB::table('products')

            ->where('type', 'motorcycle')

            ->update([

                'type' => 'part',

            ]);
    }

    public function down(): void
    {
        //
    }
};