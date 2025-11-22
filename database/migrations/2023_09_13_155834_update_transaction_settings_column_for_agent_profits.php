<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTransactionSettingsColumnForAgentProfits extends Migration {
    public function up() {
        Schema::table('transaction_settings', function (Blueprint $table) {
            if(DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE transaction_settings MODIFY slug VARCHAR(255)');
                DB::statement('ALTER TABLE transaction_settings ADD UNIQUE (slug)');
            }
            $table->decimal('agent_fixed_commissions',8,2,true)->default(0);
            $table->decimal('agent_percent_commissions',8,2,true)->default(0);
            $table->boolean('agent_profit')->default(false);
        });
    }
    public function down() {
        Schema::table('transaction_settings', function (Blueprint $table) {
            $table->dropColumn('agent_fixed_commissions');
            $table->dropColumn('agent_percent_commissions');
            $table->dropColumn('agent_profit');
        });
    }
}
