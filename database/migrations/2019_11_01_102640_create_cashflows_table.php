<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashflowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashflows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->comment('匹配USER表的用户');
            $table->enum('action',['deposit','withdraw','withdraw service fee','transfer'])->comment('分别是存款、取款、提取手续费、转账');
            $table->enum('transfer_role',['sender','recipient'])->nullable()->comment('分别是转账出款人、转账收款人');
            $table->enum('currencies',['cn','ph','us'])->comment('货币种类');
            $table->decimal('amount',10,2)->comment('金额明细');
            $table->decimal('rate',10,2)->comment('费率');
            $table->decimal('profit',10,2)->comment('服务费');
            $table->decimal('total_balance',10,2)->comment('所有账户的费用');
            $table->text('source')->nullable()->comment('备注信息');
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
        Schema::dropIfExists('cashflows');
    }
}
