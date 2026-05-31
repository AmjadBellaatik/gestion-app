<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('mail_host')->nullable()->after('invoice_footer');
            $table->unsignedSmallInteger('mail_port')->default(465)->after('mail_host');
            $table->string('mail_encryption')->nullable()->default('ssl')->after('mail_port');
            $table->string('mail_username')->nullable()->after('mail_encryption');
            $table->string('mail_password')->nullable()->after('mail_username');
            $table->string('mail_from_address')->nullable()->after('mail_password');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'mail_host',
                'mail_port',
                'mail_encryption',
                'mail_username',
                'mail_password',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};
