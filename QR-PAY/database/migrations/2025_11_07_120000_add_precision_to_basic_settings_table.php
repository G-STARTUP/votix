<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('basic_settings')) {
            return;
        }

        Schema::table('basic_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('basic_settings', 'fiat_precision_value')) {
                $table->integer('fiat_precision_value')->default(4)->after('broadcast_activity');
            }
            if (! Schema::hasColumn('basic_settings', 'crypto_precision_value')) {
                $table->integer('crypto_precision_value')->default(8)->after('fiat_precision_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('basic_settings')) {
            return;
        }

        Schema::table('basic_settings', function (Blueprint $table) {
            if (Schema::hasColumn('basic_settings', 'crypto_precision_value')) {
                $table->dropColumn('crypto_precision_value');
            }
            if (Schema::hasColumn('basic_settings', 'fiat_precision_value')) {
                $table->dropColumn('fiat_precision_value');
            }
        });
    }
};
