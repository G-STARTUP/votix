<?php

use App\Constants\PaymentGatewayConst;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPaymentLinkColumn extends Migration
{

    public function up()
    {
        Schema::table('payment_links', function (Blueprint $table) {
            // Skip MySQL-specific MODIFY on sqlite driver (testing environment)
            if(DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE payment_links MODIFY user_id BIGINT UNSIGNED NULL');
            }
            $table->foreignId('merchant_id')->nullable()->after('user_id')->constrained('merchants')->cascadeOnDelete();
        });
    }
    public function down()
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropColumn('merchant_id');
        });
    }
}
