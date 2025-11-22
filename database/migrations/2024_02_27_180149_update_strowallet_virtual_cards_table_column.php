<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if(DB::getDriverName() !== 'sqlite') {
            DB::statement('
                ALTER TABLE strowallet_virtual_cards
                MODIFY COLUMN card_name VARCHAR(255) NULL,
                MODIFY COLUMN card_number VARCHAR(255) NULL,
                MODIFY COLUMN last4 VARCHAR(4) NULL,
                MODIFY COLUMN cvv VARCHAR(3) NULL,
                MODIFY COLUMN expiry VARCHAR(5) NULL,
                MODIFY COLUMN customer_email VARCHAR(255) NULL,
                MODIFY COLUMN balance VARCHAR(255) NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
