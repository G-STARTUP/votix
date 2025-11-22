<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if(!Schema::hasColumn('transactions','receiver_address')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('receiver_address',191)->nullable()->index()->after('remark');
            });
        }
    }
    public function down(): void {
        if(Schema::hasColumn('transactions','receiver_address')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['receiver_address']);
                $table->dropColumn('receiver_address');
            });
        }
    }
};
