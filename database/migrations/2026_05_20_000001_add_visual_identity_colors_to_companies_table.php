<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'primary_color')) {
                $table->string('primary_color', 7)
                    ->default('#f59e0b')
                    ->after('logo');
            }

            if (! Schema::hasColumn('companies', 'secondary_color')) {
                $table->string('secondary_color', 7)
                    ->default('#111827')
                    ->after('primary_color');
            }

            if (! Schema::hasColumn('companies', 'accent_color')) {
                $table->string('accent_color', 7)
                    ->default('#2563eb')
                    ->after('secondary_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('companies', 'accent_color') ? 'accent_color' : null,
                Schema::hasColumn('companies', 'secondary_color') ? 'secondary_color' : null,
                Schema::hasColumn('companies', 'primary_color') ? 'primary_color' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
