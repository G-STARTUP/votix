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
        Schema::table('blog_categories', function (Blueprint $table) {
            if(DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE blog_categories MODIFY name VARCHAR(255) NULL');
                DB::statement('ALTER TABLE blog_categories MODIFY COLUMN slug VARCHAR(255) NULL');
            }
            $table->longText('data')->nullable()->after('name');
        });
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
