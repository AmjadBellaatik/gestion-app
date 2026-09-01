<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standard Laravel `notifications` table.
 *
 * The Filament admin panel enables database notifications
 * (AdminPanelProvider::panel()->databaseNotifications()), and the panel
 * topbar counts unread rows in this table on every authenticated request.
 * No migration in the repository created it, so a fresh install threw
 * "Base table or view not found: 1146 Table '...notifications' doesn't
 * exist" the first time the Super Admin opened /admin.
 *
 * Guarded so it is a no-op where the table already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
