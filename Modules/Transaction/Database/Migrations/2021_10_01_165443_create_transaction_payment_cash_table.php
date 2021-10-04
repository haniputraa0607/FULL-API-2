<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentCashTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_cash', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_payment_cash');
            $table->unsignedInteger('id_transaction');
            $table->string('payment_code', 50);
            $table->integer('cash_nominal');
            $table->unsignedInteger('cash_received_by')->nullable();
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
        Schema::dropIfExists('transaction_payment_cash');
    }
}
