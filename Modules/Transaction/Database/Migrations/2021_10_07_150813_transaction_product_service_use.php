<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TransactionProductServiceUse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_service_use', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_service_use');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_transaction_product_service');
            $table->unsignedInteger('id_product');
            $table->integer('quantity_use');
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
        //
    }
}
