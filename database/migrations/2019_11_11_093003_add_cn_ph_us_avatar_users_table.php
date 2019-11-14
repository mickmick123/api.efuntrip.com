<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCnPhUsAvatarUsersTable extends Migration
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
            $table->string('img',100)->nullable()->comment('头像');
            $table->decimal('cn',10,2)->default(0)->nullable()->comment('用户人民币金额');
            $table->decimal('ph',10,2)->default(0)->nullable()->comment('用户皮索金额');
            $table->decimal('us',10,2)->default(0)->nullable()->comment('用户美金金额');
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
