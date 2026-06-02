<?php

use App\Models\Reseller;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Reseller::withoutGlobalScopes()->get()->each(function (Reseller $reseller) {
            $reseller->recalculate();
        });
    }

    public function down(): void
    {
        // irreversible data update
    }
};
