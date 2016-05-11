<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('express', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_id'); // 订单号
            $table->string('api_status'); // 快递接口查询状态
            $table->string('express_company'); // 快递公司名称
            $table->string('express_number'); // 快递单号
            $table->string('ischeck_packer'); // 是否已经签收
            $table->string('express_state'); // 快递单当前签收状态，包括0在途中、1已揽收、2疑难、3已签收、4退签、5同城派送中、6退回、7转单等7个状态，其中4-7需要另外开通才有效
            $table->text('express_data'); // 物流详情
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
        Schema::drop('express');
    }
}
