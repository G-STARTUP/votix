<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if(!Schema::hasColumn('payment_gateway_currencies','status')) {
            Schema::table('payment_gateway_currencies', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('rate');
            });
        }
    }
    public function down() {
        if(Schema::hasColumn('payment_gateway_currencies','status')) {
            Schema::table('payment_gateway_currencies', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
