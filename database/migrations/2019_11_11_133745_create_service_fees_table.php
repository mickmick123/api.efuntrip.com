<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_fees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->comment('匹配USER表的用户');
            $table->enum('action',['deposit','withdraw service fee'])->comment('分别是存款、提取手续费');
            $table->enum('currencies',['cn','ph','us'])->comment('货币种类');
            $table->decimal('amount',10,2)->comment('金额明细');
            $table->decimal('rate',10,2)->comment('费率');
            $table->decimal('profit',10,2)->comment('服务费');
            $table->decimal('total_ServiceFee',10,2)->comment('所有账户的服务费');
            $table->text('source')->nullable()->comment('备注信息');
            $table->string('nickname',20)->comment('转账用户名');
            $table->string('operator',20)->comment('操作者');
            $table->string('number',20)->nullable()->comment('number/手机号码');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_fees');
    }
}
