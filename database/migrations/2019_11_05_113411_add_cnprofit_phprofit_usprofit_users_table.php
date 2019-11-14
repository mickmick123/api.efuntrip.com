<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCnprofitPhprofitUsprofitUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('users',function(Blueprint $table){
            $table->decimal('cnprofit',10,2)->nullable()->comment('当前人民币服务费');
            $table->decimal('phprofit',10,2)->nullable()->comment('当前皮索用户服务费');
            $table->decimal('usprofit',10,2)->nullable()->comment('当前美元用户服务费');
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
}
