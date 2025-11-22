<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('user_support_tickets', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->after('user_id')->nullable();
            }
            // Skip change() when using SQLite as it requires Doctrine DBAL
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->string('name')->nullable()->change();
            }
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('user_support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('user_support_tickets', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->string('name')->nullable(false)->change();
            }
        });
    }
};
