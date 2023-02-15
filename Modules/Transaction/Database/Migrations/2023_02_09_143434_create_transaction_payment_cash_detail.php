<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentCashDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_cash_details', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_payment_cash_detail');
            $table->unsignedInteger('id_transaction_payment_cash');
            $table->unsignedInteger('id_transaction_product');
            $table->unsignedInteger('id_outlet_cash');
            $table->unsignedInteger('cash_received_by');
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
        Schema::dropIfExists('transaction_payment_cash_details');
    }
}
